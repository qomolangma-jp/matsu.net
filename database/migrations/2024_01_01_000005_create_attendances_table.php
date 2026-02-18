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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade')->comment('イベントID');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ユーザーID');
            $table->enum('status', ['attending', 'absent', 'pending'])->default('pending')->comment('出欠ステータス');
            $table->integer('guests_count')->default(0)->comment('同伴者数');
            $table->text('remarks')->nullable()->comment('備考・メッセージ');
            $table->datetime('responded_at')->nullable()->comment('回答日時');
            $table->timestamps();

            // 複合ユニークキー（同じイベントに同じユーザーは1回のみ登録）
            $table->unique(['event_id', 'user_id']);

            // インデックス
            $table->index('status');
            $table->index('responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
