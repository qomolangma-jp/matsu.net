<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key'         => 'liff_id',
            'value'       => '',
            'type'        => 'string',
            'label'       => 'LIFF ID',
            'description' => 'LINE Front-end Framework の LIFF ID（例: 1234567890-abcdefgh）。設定するとLINE通知のリンクがLIFF URL経由になります。',
            'group'       => 'line',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'liff_id')->delete();
    }
};
