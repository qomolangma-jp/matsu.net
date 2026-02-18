<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    /**
     * 名簿一覧画面
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // クエリビルダー
        $query = User::query()
            ->filterByPermission($user)
            ->with(['approver', 'categories']);

        // 絞り込み条件
        $filters = [
            'graduation_year' => $request->input('graduation_year'),
            'category_id' => $request->input('category_id'),
            'mail_unreachable' => $request->input('mail_unreachable'),
            'approval_status' => $request->input('approval_status'),
            'role' => $request->input('role'),
            'search' => $request->input('search'),
        ];

        // フィルタ適用
        $query->byGraduationYear($filters['graduation_year'])
              ->byMailUnreachable($filters['mail_unreachable'])
              ->byApprovalStatus($filters['approval_status'])
              ->byRole($filters['role'])
              ->search($filters['search']);

        // カテゴリーでフィルタ
        if ($filters['category_id']) {
            $query->inCategory($filters['category_id']);
        }

        // ソート
        $sortBy = $request->input('sort_by', 'graduation_year');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder)
              ->orderBy('last_name_kana', 'asc');

        // ページネーション
        $users = $query->paginate(50)->withQueryString();

        // 卒業年度リスト（ドロップダウン用）
        $graduationYears = $this->getGraduationYearsList($user);

        // 地区会リスト（ドロップダウン用）
        $categories = $this->getCategoriesList($user);

        // 統計情報
        $stats = $this->getStatistics($user);

        return view('admin.users.index', compact(
            'users',
            'filters',
            'graduationYears',
            'categories',
            'stats',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * CSVエクスポート
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // クエリビルダー（ページネーションなし）
        $query = User::query()
            ->filterByPermission($user)
            ->with(['approver', 'categories']);

        // 絞り込み条件（一覧と同じ）
        $query->byGraduationYear($request->input('graduation_year'))
              ->byMailUnreachable($request->input('mail_unreachable'))
              ->byApprovalStatus($request->input('approval_status'))
              ->byRole($request->input('role'))
              ->search($request->input('search'));

        // カテゴリーでフィルタ
        if ($request->input('category_id')) {
            $query->inCategory($request->input('category_id'));
        }

        // ソート
        $sortBy = $request->input('sort_by', 'graduation_year');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder)
              ->orderBy('last_name_kana', 'asc');

        $users = $query->get();

        // CSV生成
        $filename = 'matsu_net_roster_' . date('YmdHis') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // BOM追加（Excel対応）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // ヘッダー行
            fputcsv($file, [
                'ID',
                '卒業年度',
                '回期',
                '姓',
                '名',
                '姓（カナ）',
                '名（カナ）',
                '生年月日',
                'メールアドレス',
                '郵便番号',
                '住所',
                '地区会',
                '郵送物不達',
                '権限',
                '承認ステータス',
                '承認日時',
                '承認者',
                '登録日時',
            ]);

            // データ行
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->graduation_year,
                    $user->graduation_year - 1947,
                    $user->last_name,
                    $user->first_name,
                    $user->last_name_kana,
                    $user->first_name_kana,
                    $user->birth_date?->format('Y-m-d'),
                    $user->email,
                    $user->postal_code,
                    $user->address,
                    implode(', ', $user->category_names),
                    $user->mail_unreachable ? '不達' : '',
                    $this->getRoleLabel($user->role),
                    $this->getApprovalStatusLabel($user->approval_status),
                    $user->approved_at?->format('Y-m-d H:i'),
                    $user->approver?->full_name,
                    $user->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * ユーザー編集画面
     */
    public function edit(User $user)
    {
        $admin = Auth::user();

        // 権限チェック
        if (!in_array($admin->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者の場合、自学年のみ編集可能
        if ($admin->role === 'year_admin' && $admin->graduation_year !== $user->graduation_year) {
            abort(403, '他学年のユーザーは編集できません。');
        }

        // カテゴリーリスト
        $categories = $this->getCategoriesList($admin);

        return view('admin.users.edit', compact('user', 'categories'));
    }

    /**
     * ユーザー情報更新
     */
    public function update(Request $request, User $user)
    {
        $admin = Auth::user();

        // 権限チェック
        if (!in_array($admin->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者の場合、自学年のみ更新可能
        if ($admin->role === 'year_admin' && $admin->graduation_year !== $user->graduation_year) {
            abort(403, '他学年のユーザーは更新できません。');
        }

        // バリデーション
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name_kana' => 'nullable|string|max:255',
            'first_name_kana' => 'nullable|string|max:255',
            'birth_date' => 'required|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'mail_unreachable' => 'boolean',
            'role' => 'required|in:general,year_admin,master_admin',
            'approval_status' => 'required|in:pending,approved,rejected',
        ]);

        DB::beginTransaction();
        try {
            // 学年管理者はroleを変更できない
            if ($admin->role === 'year_admin') {
                unset($validated['role']);
            }

            // 承認ステータスが変更された場合
            if ($validated['approval_status'] !== $user->approval_status) {
                if ($validated['approval_status'] === 'approved') {
                    $validated['approved_at'] = now();
                    $validated['approved_by'] = $admin->id;
                    $validated['approval_note'] = $request->input('approval_note', '管理者により承認');
                } else {
                    $validated['approved_at'] = null;
                    $validated['approved_by'] = null;
                }
            }

            // カテゴリーを除外（別途syncで更新）
            $categories = $validated['categories'] ?? [];
            unset($validated['categories']);

            $user->update($validated);

            // カテゴリーを同期（中間テーブルを更新）
            $user->categories()->sync($categories);

            DB::commit();

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => $admin->id,
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "{$user->full_name}さんの情報を更新しました。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', '更新処理に失敗しました。');
        }
    }

    /**
     * ユーザー承認
     */
    public function approve(Request $request, User $user)
    {
        $admin = Auth::user();

        // 権限チェック
        if (!in_array($admin->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者の場合、自学年のみ承認可能
        if ($admin->role === 'year_admin' && $admin->graduation_year !== $user->graduation_year) {
            abort(403, '他学年のユーザーは承認できません。');
        }

        DB::beginTransaction();
        try {
            $user->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'approval_note' => $request->input('note', '管理者により承認'),
            ]);

            // 参照名簿の登録済みフラグ更新（該当する場合）
            DB::table('reference_rosters')
                ->where('last_name', $user->last_name)
                ->where('first_name', $user->first_name)
                ->where('graduation_year', $user->graduation_year)
                ->update(['is_registered' => true]);

            DB::commit();

            Log::info('User approved', [
                'user_id' => $user->id,
                'approved_by' => $admin->id,
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "{$user->full_name}さんを承認しました。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User approval failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->with('error', '承認処理に失敗しました。');
        }
    }

    /**
     * ユーザー却下
     */
    public function reject(Request $request, User $user)
    {
        $admin = Auth::user();

        // 権限チェック
        if (!in_array($admin->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者の場合、自学年のみ却下可能
        if ($admin->role === 'year_admin' && $admin->graduation_year !== $user->graduation_year) {
            abort(403, '他学年のユーザーは却下できません。');
        }

        DB::beginTransaction();
        try {
            $user->update([
                'approval_status' => 'rejected',
                'approved_by' => $admin->id,
                'approval_note' => $request->input('note', '管理者により却下'),
            ]);

            DB::commit();

            Log::info('User rejected', [
                'user_id' => $user->id,
                'rejected_by' => $admin->id,
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "{$user->full_name}さんの申請を却下しました。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User rejection failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->with('error', '却下処理に失敗しました。');
        }
    }

    /**
     * 卒業年度リストを取得
     */
    private function getGraduationYearsList($user)
    {
        $query = User::query();
        
        if ($user->role === 'year_admin') {
            $query->where('graduation_year', $user->graduation_year);
        }

        return $query->select('graduation_year')
            ->distinct()
            ->orderBy('graduation_year', 'desc')
            ->pluck('graduation_year');
    }

    /**
     * カテゴリーリストを取得
     */
    private function getCategoriesList($user)
    {
        // Category モデルからアクティブなカテゴリーを取得
        return \App\Models\Category::active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * 統計情報を取得
     */
    private function getStatistics($user)
    {
        $query = User::query()->filterByPermission($user);

        return [
            'total' => (clone $query)->count(),
            'approved' => (clone $query)->where('approval_status', 'approved')->count(),
            'pending' => (clone $query)->where('approval_status', 'pending')->count(),
            'rejected' => (clone $query)->where('approval_status', 'rejected')->count(),
            'mail_unreachable' => (clone $query)->where('mail_unreachable', true)->count(),
        ];
    }

    /**
     * 権限ラベル取得
     */
    private function getRoleLabel($role)
    {
        return [
            'general' => '一般ユーザー',
            'year_admin' => '学年管理者',
            'master_admin' => 'マスター管理者',
        ][$role] ?? $role;
    }

    /**
     * 承認ステータスラベル取得
     */
    private function getApprovalStatusLabel($status)
    {
        return [
            'pending' => '承認待ち',
            'approved' => '承認済み',
            'rejected' => '却下',
        ][$status] ?? $status;
    }
}
