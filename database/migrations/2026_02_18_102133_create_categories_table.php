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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('カテゴリー名（例: 東京地区会）');
            $table->string('slug', 100)->unique()->comment('URL用スラッグ（例: tokyo）');
            $table->text('description')->nullable()->comment('説明');
            $table->enum('type', ['district', 'role', 'other'])->default('district')->comment('タイプ（地区会、役職、その他）');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効/無効');
            $table->timestamps();
            
            // インデックス
            $table->index('name');
            $table->index('slug');
            $table->index('type');
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
