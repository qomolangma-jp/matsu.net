<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // 既存テーブルにカラムがなければ追加（idempotent）
        if (!Schema::hasColumn('line_notification_logs', 'notification_month')) {
            Schema::table('line_notification_logs', function (Blueprint $table) {
                // 月別記録用（YYYY-MM形式）
                $table->string('notification_month')->nullable()->index()->after('created_at');
            });

            // 既存レコードに対して created_at から月を逆算してセット
            DB::statement("
                UPDATE line_notification_logs 
                SET notification_month = DATE_FORMAT(created_at, '%Y-%m')
                WHERE notification_month IS NULL
            ");

            // notification_monthをNOT NULLに変更
            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->string('notification_month')->nullable(false)->change();
            });
        }

        // Settings に LINE 月間上限設定を追加
        DB::table('settings')->updateOrInsert(
            ['key' => 'line_push_limit'],
            [
                'value' => '200',
                'type' => 'integer',
                'label' => 'LINE プッシュ通知月間上限',
                'description' => 'LINE プッシュ通知の月間送信上限数（無料プランは200通）',
                'group' => 'line',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Settings に 現在の月を記録
        DB::table('settings')->updateOrInsert(
            ['key' => 'line_push_current_month'],
            [
                'value' => Carbon::now()->format('Y-m'),
                'type' => 'string',
                'label' => 'LINE プッシュ通知現在の月',
                'description' => '月間送信カウント用の現在の月（YYYY-MM）',
                'group' => 'line',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('line_notification_logs', function (Blueprint $table) {
            $table->dropColumn('notification_month');
        });

        DB::table('settings')->where('key', 'line_push_limit')->delete();
        DB::table('settings')->where('key', 'line_push_current_month')->delete();
    }
};
