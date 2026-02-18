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
        Schema::create('category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ユーザーID');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade')->comment('カテゴリーID');
            $table->timestamp('assigned_at')->nullable()->comment('割り当て日時');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null')->comment('割り当てた管理者のuser_id');
            $table->text('notes')->nullable()->comment('メモ');
            $table->timestamps();
            
            // 複合ユニークキー（同じユーザーに同じカテゴリーを重複して割り当てない）
            $table->unique(['user_id', 'category_id']);
            
            // インデックス
            $table->index('user_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_user');
    }
};
