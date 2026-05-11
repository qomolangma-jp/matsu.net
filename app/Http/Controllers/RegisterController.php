<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferenceRoster;
use App\Models\Setting;
use App\Mail\ApprovalRequestMail;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * 新規登録フォーム表示
     * ローカル環境では既存ユーザーを自動ログイン
     */
    public function showForm(Request $request)
    {
        // ローカル環境のみ：既存ユーザーの自動ログイン
        if (config('app.env') === 'local') {
            $lineId = $request->input('line_id') ?? $request->session()->get('temp_line_id');
            
            if ($lineId) {
                $existingUser = User::where('line_id', $lineId)->first();
                
                if ($existingUser) {
                    // 既存ユーザーをログイン
                    Auth::login($existingUser);
                    
                    Log::info('ローカル環境：既存ユーザーを自動ログイン', [
                        'line_id' => $lineId,
                        'user_id' => $existingUser->id,
                        'name' => $existingUser->full_name,
                    ]);
                    
                    // 管理者権限があれば管理画面へ、なければマイページへ
                    if (in_array($existingUser->role, ['master_admin', 'year_admin'])) {
                        return redirect()->route('admin.users.index')
                            ->with('success', "おかえりなさい、{$existingUser->full_name}さん（自動ログイン）");
                    } else {
                        return redirect()->route('mypage.index')
                            ->with('success', "おかえりなさい、{$existingUser->full_name}さん（自動ログイン）");
                    }
                }
            }
        }
        
        // 登録受付停止チェック
        if (!Setting::get('registration_open', true)) {
            $message = Setting::get('registration_closed_message', '現在、新規登録の受付を停止しています。');
            return view('register-closed', compact('message'));
        }

        // /auth/line から引き継いだ line_id（未登録ユーザー）
        $lineId = $request->input('line_id', '');

        return view('register', compact('lineId'));
    }

    /**
     * LIFF LINE ID チェック（本番・ローカル共通）
     * JS が LINE ID 取得後にここへリダイレクト。
     * - 既存ユーザー → 自動ログイン → マイページ（または管理画面）
     * - 未登録       → 登録フォームへ（line_id をクエリに引き継ぐ）
     */
    public function authWithLine(Request $request)
    {
        $lineId   = $request->input('line_id');
        $redirect = $request->input('redirect', '');
        $safePath = preg_match('#^/(events|news)/\d+$#', $redirect) ? $redirect : '/mypage';

        Log::info('authWithLine アクセス', [
            'line_id' => $lineId,
            'redirect' => $redirect,
            'session_id' => $request->session()->getId(),
        ]);

        if (!$lineId) {
            return redirect()->route('register.form');
        }

        $existingUser = User::where('line_id', $lineId)->first();

        if ($existingUser) {
            Auth::login($existingUser, true); // true = remember_me
            $request->session()->regenerate(); // セッション固定攻撃対策と保存の明示的実行
            $request->session()->save();       // Cookie発行前に明示的保存

            Log::info('LIFF：既存ユーザーを自動ログイン', [
                'line_id'  => $lineId,
                'user_id'  => $existingUser->id,
                'name'     => $existingUser->full_name,
                'redirect' => $safePath,
                'new_session_id' => $request->session()->getId(),
                'is_secure' => $request->secure(),
            ]);

            if (in_array($existingUser->role, ['master_admin', 'year_admin'])) {
                return redirect($safePath === '/mypage' ? route('admin.users.index') : $safePath)
                    ->with('success', "おかえりなさい、{$existingUser->full_name}さん");
            }

            return redirect($safePath)
                ->with('success', "おかえりなさい、{$existingUser->full_name}さん");
        }

        Log::info('LIFF：未登録ユーザーのため登録フォームへ', ['line_id' => $lineId]);
        // 未登録：登録フォームへ（line_id を引き継ぐ）
        return redirect()->route('register.form', ['line_id' => $lineId]);
    }

    /**
     * 新規登録処理（LIFF経由）
     * 
     * @param RegisterRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RegisterRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // 卒業年度を数値に変換（usersテーブルはinteger型）
            $graduationYear = $request->getGraduationYear();
            
            if (!$graduationYear) {
                return redirect()->back()
                    ->withErrors(['graduation_term' => '卒業年度の形式が正しくありません。'])
                    ->withInput();
            }

            // 参照名簿と照合（スペースを除去した検索用氏名で照合）
            $matchResult = $this->matchWithReferenceRoster($request);

            // ユーザー作成
            $user = User::create([
                'line_id' => $request->line_id,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'last_name_kana' => $request->last_name_kana,
                'first_name_kana' => $request->first_name_kana,
                'birth_date' => $request->birth_date,
                'graduation_year' => $graduationYear,
                'email' => $request->email,
                'postal_code' => $request->postal_code,
                'address' => $request->address,
                'role' => 'general', // 初期登録は一般ユーザー
                'approval_status' => $matchResult['status'], // 'approved' or 'pending'
            ]);

            // 完全一致の場合は自動承認
            if ($matchResult['status'] === 'approved') {
                $user->update([
                    'approved_at' => now(),
                    'approval_note' => '参照名簿と完全一致のため自動承認',
                ]);

                // 参照名簿の登録済みフラグを更新
                if ($matchResult['matched_roster']) {
                    $matchResult['matched_roster']->update(['is_registered' => true]);
                }

                DB::commit();
// オートログイン
                Auth::login($user);

                
                return redirect()->route('mypage.index')
                    ->with('success', '登録が完了しました。');
            }

            // 部分一致または不一致の場合は承認待ちメールを送信
            $this->sendApprovalRequest($user, $matchResult);
// オートログイン（承認待ちでもログインさせる）
            Auth::login($user);

            
            DB::commit();

            return redirect()->route('mypage.index')
                ->with('success', '登録申請を受け付けました。承認をお待ちください。')
                ->with('pending', true);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => '登録に失敗しました。もう一度お試しください。'])
                ->withInput();
        }
    }

    /**
     * 参照名簿との照合（スペース除去による精度向上版）
     * 
     * @param RegisterRequest $request
     * @return array ['status' => string, 'match_type' => string, 'matched_roster' => ReferenceRoster|null]
     */
    private function matchWithReferenceRoster(RegisterRequest $request): array
    {
        $graduationTerm = $request->graduation_term; // 例: 高校51回期
        $searchName = $request->getSearchName(); // スペース除去済み氏名
        $searchKana = $request->getSearchKana(); // スペース除去済みカナ（nullの場合あり）
        
        Log::info('参照名簿照合開始', [
            'graduation_term' => $graduationTerm,
            'search_name' => $searchName,
            'search_kana' => $searchKana,
        ]);

        // 1. 完全一致チェック（卒業回 + 氏名、スペースを無視して比較）
        // REPLACE関数でDBのnameカラムからもスペースを除去して比較
        $exactMatches = ReferenceRoster::where('graduation_term', $graduationTerm)
            ->whereRaw("REPLACE(REPLACE(REPLACE(name, ' ', ''), '　', ''), '\t', '') = ?", [$searchName])
            ->get();

        // 完全一致が1件のみの場合は自動承認
        if ($exactMatches->count() === 1) {
            $matched = $exactMatches->first();
            
            Log::info('参照名簿と完全一致（自動承認）', [
                'matched_id' => $matched->id,
                'matched_name' => $matched->name,
            ]);
            
            return [
                'status' => 'approved', // 利用許可
                'match_type' => 'exact',
                'matched_roster' => $matched,
            ];
        }

        // 複数一致した場合
        if ($exactMatches->count() > 1) {
            Log::warning('参照名簿に複数一致（保留）', [
                'count' => $exactMatches->count(),
                'matched_ids' => $exactMatches->pluck('id')->toArray(),
            ]);
            
            return [
                'status' => 'pending', // 保留（複数該当）
                'match_type' => 'multiple',
                'matched_roster' => null,
            ];
        }

        // 2. カナでの一致チェック（カナが入力されている場合）
        if ($searchKana) {
            $kanaMatches = ReferenceRoster::where('graduation_term', $graduationTerm)
                ->whereRaw("REPLACE(REPLACE(REPLACE(kana, ' ', ''), '　', ''), '\t', '') = ?", [$searchKana])
                ->get();
            
            if ($kanaMatches->count() === 1) {
                Log::info('参照名簿とカナで一致（保留）', [
                    'matched_id' => $kanaMatches->first()->id,
                    'matched_name' => $kanaMatches->first()->name,
                ]);
                
                return [
                    'status' => 'pending', // カナ一致は保留扱い
                    'match_type' => 'kana',
                    'matched_roster' => $kanaMatches->first(),
                ];
            }
        }

        // 3. 一致しない場合
        Log::warning('参照名簿と一致せず（保留）', [
            'graduation_term' => $graduationTerm,
            'search_name' => $searchName,
        ]);
        
        return [
            'status' => 'pending', // 保留（該当なし）
            'match_type' => 'none',
            'matched_roster' => null,
        ];
    }
    
    /**
     * 卒業年度を卒業回に変換
     * 
     * @param int $year
     * @return string
     */
    private function convertYearToTerm(int $year): string
    {
        // 例: 2018年卒業 = 高校51回期
        // ※この変換ロジックは実際の卒業回の命名規則に基づいて調整してください
        // 仮の計算式: 卒業年度 - 1967 = 回期（例: 2018 - 1967 = 51）
        $term = $year - 1967;
        return "高校{$term}回期";
    }
    
    /**
     * 承認依頼メール送信
     * 優先順位: 1. 学年管理者（grade_admin） → 2. マスター管理者（master）
     * 
     * @param User $user
     * @param array $matchResult
     * @return void
     */
    private function sendApprovalRequest(User $user, array $matchResult): void
    {
        // 優先順位1: 該当学年の学年管理者を検索
        $approver = User::where('graduation_year', $user->graduation_year)
            ->whereIn('role', ['year_admin', 'grade_admin']) // 旧名称と新名称両方に対応
            ->where('approval_status', 'approved')
            ->whereNotNull('email')
            ->first();

        // 優先順位2: 学年管理者がいない場合はマスター管理者を検索
        if (!$approver) {
            $approver = User::whereIn('role', ['master_admin', 'master']) // 旧名称と新名称両方に対応
                ->where('approval_status', 'approved')
                ->whereNotNull('email')
                ->first();
        }

        // 承認者が見つからない場合はログに記録して終了
        if (!$approver) {
            Log::warning('承認者が見つかりません', [
                'user_id' => $user->id,
                'graduation_year' => $user->graduation_year,
                'full_name' => $user->full_name,
            ]);
            return;
        }

        // 承認依頼メール送信
        try {
            $matchedData = [];
            if ($matchResult['matched_roster']) {
                $matchedData = $matchResult['matched_roster']->toArray();
            }

            // メールを送信（非同期キュー対応）
            Mail::to($approver->email)->send(
                new ApprovalRequestMail(
                    $user,
                    $matchResult['match_type'],
                    $matchedData,
                    $approver->role // 承認者の権限を渡す
                )
            );

            Log::info('承認依頼メールを送信しました', [
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'approver_id' => $approver->id,
                'approver_email' => $approver->email,
                'approver_role' => $approver->role,
                'match_type' => $matchResult['match_type'],
            ]);
        } catch (\Exception $e) {
            Log::error('承認依頼メールの送信に失敗しました', [
                'user_id' => $user->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
