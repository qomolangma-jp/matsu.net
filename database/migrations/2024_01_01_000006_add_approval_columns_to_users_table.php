<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('role')->comment('承認ステータス');
            $table->timestamp('approved_at')->nullable()->after('approval_status')->comment('承認日時');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approved_at')->comment('承認者ID');
            $table->text('approval_note')->nullable()->after('approved_by')->comment('承認メモ');
            
            // インデックス追加
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'approved_at', 'approved_by', 'approval_note']);
        });
    }
};
