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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('イベント名');
            $table->text('description')->nullable()->comment('説明');
            $table->datetime('event_date')->comment('イベント日時');
            $table->string('location')->nullable()->comment('場所');
            $table->year('graduation_year')->nullable()->comment('対象卒業年度（nullの場合は全体向け）');
            $table->integer('capacity')->nullable()->comment('定員');
            $table->datetime('deadline')->nullable()->comment('申込締切');
            $table->foreignId('created_by')->constrained('users')->comment('作成者');
            $table->boolean('is_published')->default(true)->comment('公開フラグ');
            $table->timestamps();
            $table->softDeletes();

            // インデックス
            $table->index('event_date');
            $table->index('graduation_year');
            $table->index('deadline');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
