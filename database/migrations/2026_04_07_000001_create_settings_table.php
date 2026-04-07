<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, text
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // デフォルト値を挿入
        DB::table('settings')->insert([
            // 基本情報
            [
                'key'         => 'site_name',
                'value'       => '松.net',
                'type'        => 'string',
                'label'       => 'サイト名',
                'description' => 'ページタイトルやメール件名に使用されます',
                'group'       => 'general',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'association_name',
                'value'       => '松高同窓会',
                'type'        => 'string',
                'label'       => '同窓会名',
                'description' => 'メール本文等に使用されます',
                'group'       => 'general',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'admin_email',
                'value'       => '',
                'type'        => 'string',
                'label'       => '管理者メールアドレス',
                'description' => '承認依頼メール等の送信先',
                'group'       => 'general',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            // 登録受付
            [
                'key'         => 'registration_open',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => '新規登録受付',
                'description' => 'OFFにすると新規登録フォームが利用できなくなります',
                'group'       => 'registration',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'registration_closed_message',
                'value'       => '現在、新規登録の受付を停止しています。',
                'type'        => 'text',
                'label'       => '受付停止時のメッセージ',
                'description' => '登録受付をOFFにしたとき、フォームの代わりに表示するメッセージ',
                'group'       => 'registration',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            // LINE設定
            [
                'key'         => 'line_channel_access_token',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'LINE Channel Access Token',
                'description' => 'LINE Messaging API のチャンネルアクセストークン（設定するとenv設定より優先されます）',
                'group'       => 'line',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
