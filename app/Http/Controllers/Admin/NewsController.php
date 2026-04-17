<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\User;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    private LineMessagingService $lineService;

    public function __construct(LineMessagingService $lineService)
    {
        $this->lineService = $lineService;
    }

    /**
     * ニュース一覧
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $query = News::with('creator')
            ->withCount('lineNotificationLogs as line_sent_count')
            ->orderBy('created_at', 'desc');

        // 学年管理者は自学年関連のニュースのみ
        if ($user->role === 'year_admin') {
            $query->where(function($q) use ($user) {
                $q->whereNull('target_graduation_years')
                  ->orWhereJsonContains('target_graduation_years', $user->graduation_year);
            });
        }

        $news = $query->paginate(20);

        return view('admin.news.index', compact('news'));
    }

    /**
     * ニュース作成画面
     */
    public function create()
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 卒業年度リスト
        $graduationYears = $this->getGraduationYearsList($user);

        return view('admin.news.create', compact('graduationYears'));
    }

    /**
     * ニュース保存
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_graduation_years' => 'nullable|array',
            'target_graduation_years.*' => 'integer|min:1948',
            'is_line_notification' => 'boolean',
            'is_top_display' => 'boolean',
            'publish_now' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // 学年管理者の場合、自学年のみ指定可能
            $targetYears = $request->input('target_graduation_years', []);
            if ($user->role === 'year_admin') {
                // 自学年以外が含まれていないかチェック
                foreach ($targetYears as $year) {
                    if ($year != $user->graduation_year) {
                        return back()->withErrors(['target_graduation_years' => '自学年以外は選択できません。'])->withInput();
                    }
                }
                
                // 空の場合は自学年のみに設定
                if (empty($targetYears)) {
                    $targetYears = [$user->graduation_year];
                }
            }

            $news = News::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target_graduation_years' => !empty($targetYears) ? $targetYears : null,
                'is_line_notification' => $request->boolean('is_line_notification'),
                'is_top_display' => $request->boolean('is_top_display'),
                'published_at' => $request->boolean('publish_now') ? now() : null,
                'created_by' => $user->id,
            ]);

            // LINE通知送信（作成時）
            $lineMsg = '';
            if ($request->boolean('is_line_notification')) {
                $result = $this->lineService->sendNotification($news, false);
                $lineMsg = "LINE通知: {$result['success_count']}件送信";
                if ($result['failure_count'] > 0) {
                    $lineMsg .= "（{$result['failure_count']}件失敗）";
                }
                Log::info('ニュースLINE送信完了', [
                    'news_id' => $news->id,
                    'success_count' => $result['success_count'],
                    'failure_count' => $result['failure_count'],
                ]);
            }

            DB::commit();

            $successMsg = 'ニュースを作成しました。' . ($lineMsg ? ' ' . $lineMsg : '');
            return redirect()->route('admin.news.index')
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ニュース作成エラー', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'ニュースの作成に失敗しました。'])->withInput();
        }
    }

    /**
     * ニュース編集画面
     */
    public function edit(News $news)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年関連のニュースのみ編集可能
        if ($user->role === 'year_admin') {
            if ($news->graduation_year && $news->graduation_year != $user->graduation_year) {
                abort(403, '他学年のニュースは編集できません。');
            }
        }

        $graduationYears = $this->getGraduationYearsList($user);

        // LINE送信統計
        $lineSentCount = $news->lineNotificationLogs()->distinct('user_id')->count('user_id');
        $targetUsersQuery = User::approved()->whereNotNull('line_id');
        if (!empty($news->target_graduation_years)) {
            $targetUsersQuery->whereIn('graduation_year', $news->target_graduation_years);
        }
        $lineTargetCount  = $targetUsersQuery->count();
        $lineUnsentCount  = max(0, $lineTargetCount - $lineSentCount);

        return view('admin.news.edit', compact('news', 'graduationYears', 'lineSentCount', 'lineTargetCount', 'lineUnsentCount'));
    }

    /**
     * ニュース更新
     */
    public function update(Request $request, News $news)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年関連のニュースのみ更新可能
        if ($user->role === 'year_admin') {
            if ($news->target_graduation_years && !in_array($user->graduation_year, $news->target_graduation_years)) {
                abort(403, '他学年のニュースは更新できません。');
            }
        }

        $validated = $request->validate([
            'title'                    => 'required|string|max:255',
            'body'                     => 'required|string',
            'target_graduation_years'  => 'nullable|array',
            'target_graduation_years.*'=> 'integer|min:1948',
            'is_top_display'           => 'boolean',
            'send_line_to_unsent'      => 'nullable|boolean',
            'send_line_resend_all'     => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $targetYears = $request->input('target_graduation_years', []);
            if ($user->role === 'year_admin' && !empty($targetYears)) {
                foreach ($targetYears as $year) {
                    if ($year != $user->graduation_year) {
                        return back()->withErrors(['target_graduation_years' => '自学年以外は選択できません。'])->withInput();
                    }
                }
            }

            $news->update([
                'title'                  => $validated['title'],
                'body'                   => $validated['body'],
                'target_graduation_years'=> !empty($targetYears) ? $targetYears : null,
                'is_top_display'         => $request->boolean('is_top_display'),
            ]);

            // LINE送信処理（更新時）
            $lineMsg = '';
            if ($request->boolean('send_line_resend_all')) {
                $result = $this->lineService->sendNotification($news, true);
                $lineMsg = "LINE再送: {$result['success_count']}件送信";
                if ($result['failure_count'] > 0) {
                    $lineMsg .= "（{$result['failure_count']}件失敗）";
                }
            } elseif ($request->boolean('send_line_to_unsent')) {
                $result = $this->lineService->sendNotification($news, false);
                $lineMsg = "LINE送信: {$result['success_count']}件送信";
                if ($result['failure_count'] > 0) {
                    $lineMsg .= "（{$result['failure_count']}件失敗）";
                }
            }

            DB::commit();

            $successMsg = 'ニュースを更新しました。' . ($lineMsg ? ' ' . $lineMsg : '');
            return redirect()->route('admin.news.index')
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ニュース更新エラー', [
                'error'   => $e->getMessage(),
                'news_id' => $news->id,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'ニュースの更新に失敗しました。'])->withInput();
        }
    }

    /**
     * ニュース削除
     */
    public function destroy(News $news)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年関連のニュースのみ削除可能
        if ($user->role === 'year_admin') {
            if ($news->target_graduation_years && !in_array($user->graduation_year, $news->target_graduation_years)) {
                abort(403, '他学年のニュースは削除できません。');
            }
        }

        try {
            $news->delete();

            return redirect()->route('admin.news.index')
                ->with('success', 'ニュースを削除しました。');

        } catch (\Exception $e) {
            Log::error('ニュース削除エラー', [
                'error' => $e->getMessage(),
                'news_id' => $news->id,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'ニュースの削除に失敗しました。']);
        }
    }

    /**
     * 卒業年度リスト取得
     */
    private function getGraduationYearsList(User $user): array
    {
        if ($user->role === 'master_admin') {
            return User::select('graduation_year')
                ->distinct()
                ->whereNotNull('graduation_year')
                ->orderBy('graduation_year', 'desc')
                ->pluck('graduation_year')
                ->toArray();
        }

        // 学年管理者は自学年のみ
        return [$user->graduation_year];
    }
}
