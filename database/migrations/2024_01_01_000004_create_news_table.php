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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('タイトル');
            $table->text('content')->comment('本文');
            $table->foreignId('author_id')->constrained('users')->comment('投稿者');
            $table->boolean('is_top_display')->default(false)->comment('TOP掲載フラグ');
            $table->boolean('is_line_notify')->default(false)->comment('LINE通知フラグ');
            $table->datetime('published_at')->nullable()->comment('公開日時');
            $table->year('graduation_year')->nullable()->comment('対象卒業年度（nullの場合は全体向け）');
            $table->integer('display_order')->default(0)->comment('表示順（大きい数字が上位）');
            $table->timestamps();
            $table->softDeletes();

            // インデックス
            $table->index('is_top_display');
            $table->index('published_at');
            $table->index('graduation_year');
            $table->index(['is_top_display', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
