<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('categories')->where('slug', 'standing-director')->orWhere('name', '常任理事')->exists()) {
            return;
        }

        DB::table('categories')->insert([
            'name' => '常任理事',
            'slug' => 'standing-director',
            'description' => '常任理事（役職）',
            'type' => 'role',
            'display_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('categories')->where('slug', 'standing-director')->delete();
    }
};
