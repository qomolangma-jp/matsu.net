<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * カテゴリー一覧画面
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 権限チェック（マスター管理者のみ）
        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // クエリビルダー
        $query = Category::query()->withCount('users');

        // 絞り込み条件
        $filters = [
            'type' => $request->input('type'),
            'is_active' => $request->input('is_active'),
            'search' => $request->input('search'),
        ];

        // フィルタ適用
        if ($filters['type']) {
            $query->ofType($filters['type']);
        }

        if ($filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active'] === '1');
        }

        if ($filters['search']) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('slug', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('description', 'LIKE', "%{$filters['search']}%");
            });
        }

        // ソート
        $sortBy = $request->input('sort_by', 'display_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $categories = $query->paginate(50)->withQueryString();

        // 統計情報
        $stats = [
            'total' => Category::count(),
            'active' => Category::active()->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'district' => Category::district()->count(),
        ];

        return view('admin.categories.index', compact(
            'categories',
            'filters',
            'stats',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * カテゴリー作成画面
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        return view('admin.categories.create');
    }

    /**
     * カテゴリー保存
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'slug' => 'nullable|string|max:100|unique:categories,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:district,role,other',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // slugが空の場合は自動生成
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category = Category::create($validated);

            DB::commit();

            Log::info('カテゴリー作成', [
                'category_id' => $category->id,
                'name' => $category->name,
                'created_by' => $user->id,
            ]);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "カテゴリー「{$category->name}」を作成しました。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('カテゴリー作成エラー', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'カテゴリーの作成に失敗しました。');
        }
    }

    /**
     * カテゴリー編集画面
     */
    public function edit(Category $category)
    {
        $user = Auth::user();

        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * カテゴリー更新
     */
    public function update(Request $request, Category $category)
    {
        $user = Auth::user();

        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:100|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'type' => 'required|in:district,role,other',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // slugが空の場合は自動生成
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category->update($validated);

            DB::commit();

            Log::info('カテゴリー更新', [
                'category_id' => $category->id,
                'name' => $category->name,
                'updated_by' => $user->id,
            ]);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "カテゴリー「{$category->name}」を更新しました。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('カテゴリー更新エラー', [
                'error' => $e->getMessage(),
                'category_id' => $category->id,
                'user_id' => $user->id,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'カテゴリーの更新に失敗しました。');
        }
    }

    /**
     * カテゴリー削除
     */
    public function destroy(Category $category)
    {
        $user = Auth::user();

        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        DB::beginTransaction();
        try {
            $categoryName = $category->name;
            $userCount = $category->users()->count();

            // カテゴリーを削除（cascadeで中間テーブルも削除される）
            $category->delete();

            DB::commit();

            Log::info('カテゴリー削除', [
                'category_name' => $categoryName,
                'affected_users' => $userCount,
                'deleted_by' => $user->id,
            ]);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', "カテゴリー「{$categoryName}」を削除しました（{$userCount}ユーザーの関連付けも解除）。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('カテゴリー削除エラー', [
                'error' => $e->getMessage(),
                'category_id' => $category->id,
                'user_id' => $user->id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'カテゴリーの削除に失敗しました。');
        }
    }
}
