<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 既存のuser_categoryデータを新しいcategoriesテーブルに移行
     */
    public function up(): void
    {
        // 既存のuser_categoryデータから一意のカテゴリーを抽出
        $userCategories = DB::table('users')
            ->whereNotNull('user_category')
            ->where('user_category', '!=', '')
            ->select('user_category')
            ->distinct()
            ->get();

        $categoryMap = [];

        foreach ($userCategories as $uc) {
            $categoryName = trim($uc->user_category);
            
            if (empty($categoryName)) {
                continue;
            }

            // カテゴリーを作成
            $categoryId = DB::table('categories')->insertGetId([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'description' => "既存データから移行: {$categoryName}",
                'type' => 'district',
                'display_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categoryMap[$categoryName] = $categoryId;
        }

        // ユーザーとカテゴリーの関連付け
        $users = DB::table('users')
            ->whereNotNull('user_category')
            ->where('user_category', '!=', '')
            ->get();

        foreach ($users as $user) {
            $categoryName = trim($user->user_category);
            
            if (empty($categoryName) || !isset($categoryMap[$categoryName])) {
                continue;
            }

            // 中間テーブルに登録
            DB::table('category_user')->insert([
                'user_id' => $user->id,
                'category_id' => $categoryMap[$categoryName],
                'assigned_at' => $user->created_at ?? now(),
                'assigned_by' => null, // 既存データのため不明
                'notes' => '既存user_categoryカラムから移行',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 移行完了のログ
        $migratedCount = count($categoryMap);
        $userCount = count($users);
        \Log::info("カテゴリー移行完了: {$migratedCount}カテゴリー, {$userCount}ユーザー");
    }

    /**
     * Reverse the migrations.
     * ロールバック時は移行したデータを削除
     */
    public function down(): void
    {
        // 移行時に作成されたカテゴリーと関連付けを削除
        // （注意: 手動で追加されたカテゴリーも削除される可能性があります）
        DB::table('category_user')->truncate();
        DB::table('categories')->where('description', 'LIKE', '既存データから移行:%')->delete();
    }
};
