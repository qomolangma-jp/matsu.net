<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // 外部キー制約を先に削除
            $foreignKeys = collect(DB::select("SHOW CREATE TABLE news"))
                ->map(function ($row) {
                    preg_match_all('/CONSTRAINT `(.*?)` FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)` \(`(.*?)`\)/', $row->{'Create Table'}, $matches, PREG_SET_ORDER);
                    return $matches;
                })
                ->flatten(1)
                ->filter(fn($match) => $match[2] === 'author_id')
                ->map(fn($match) => $match[1])
                ->all();

            foreach ($foreignKeys as $key) {
                if (Schema::hasColumn('news', 'author_id')) {
                    $table->dropForeign($key);
                }
            }
            
            // 既存カラムを削除（存在チェック付き）
            $columnsToDrop = ['content', 'author_id', 'graduation_year', 'is_line_notify', 'display_order'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('news', function (Blueprint $table) {
            // 新しいカラムを追加（存在しない場合のみ）
            if (!Schema::hasColumn('news', 'body')) {
                $table->text('body')->after('title')->comment('本文');
            }
            if (!Schema::hasColumn('news', 'target_graduation_years')) {
                $table->json('target_graduation_years')->nullable()->after('body')->comment('対象卒業年度（JSON配列、nullの場合は全学年）');
            }
            if (!Schema::hasColumn('news', 'is_line_notification')) {
                $table->boolean('is_line_notification')->default(false)->after('target_graduation_years')->comment('LINE通知フラグ');
            }
            if (!Schema::hasColumn('news', 'created_by')) {
                $table->foreignId('created_by')->constrained('users')->comment('投稿者');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['body', 'target_graduation_years', 'is_line_notification', 'created_by']);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->text('content')->comment('本文');
            $table->foreignId('author_id')->constrained('users')->comment('投稿者');
            $table->year('graduation_year')->nullable()->comment('対象卒業年度');
            $table->boolean('is_line_notify')->default(false)->comment('LINE通知フラグ');
            $table->integer('display_order')->default(0)->comment('表示順');
        });
    }
};
