<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->json('target_roles')->nullable()->after('target_graduation_years')->comment('対象権限');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->json('target_roles')->nullable()->after('graduation_year')->comment('対象権限');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('target_roles');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('target_roles');
        });
    }
};