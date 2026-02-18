<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 約3万件のCSVデータをインポートするための参照名簿テーブル
     */
    public function up(): void
    {
        Schema::create('reference_rosters', function (Blueprint $table) {
            $table->id();
            
            // CSV列0: 卒業回 (例: 高校51回期)
            $table->string('graduation_term', 50)->comment('卒業回');
            
            // CSV列1: 氏名 (例: 相河 奈美)
            $table->string('name', 100)->comment('氏名');
            
            // CSV列2: 性別 (例: 女, 男)
            $table->string('gender', 10)->nullable()->comment('性別');
            
            // CSV列3: 状態/会員区分 (例: 一般, 不明)
            $table->string('status', 50)->nullable()->comment('状態/会員区分');
            
            // CSV列4: 役職1 (例: 理事, ×常任理事)
            $table->string('role_1', 100)->nullable()->comment('役職1');
            
            // CSV列5: 役職2 (例: 常任理事)
            $table->string('role_2', 100)->nullable()->comment('役職2');
            
            // CSV列6: 旧姓 (例: 野原, 吉田)
            $table->string('former_name', 100)->nullable()->comment('旧姓');
            
            // CSV列7: フリガナ (例: ｱｲｶﾜ ﾅﾐ 半角カナ)
            $table->string('kana', 200)->nullable()->comment('フリガナ');
            
            // CSV列8: 備考/更新履歴 (例: 2018.4郵便物返却, 2019.7氏名変更)
            $table->text('notes')->nullable()->comment('備考/更新履歴');
            
            // CSV列9: 〒 (例: 923-0931)
            $table->string('postal_code', 10)->nullable()->comment('郵便番号');
            
            // CSV列10: 住所1 (例: 石川県小松市)
            $table->string('address_1', 100)->nullable()->comment('住所1');
            
            // CSV列11: 住所2 (例: 大文字町１３０番地)
            $table->string('address_2', 100)->nullable()->comment('住所2');
            
            // CSV列12: 住所3 (例: 空白やマンション名など)
            $table->string('address_3', 100)->nullable()->comment('住所3');
            
            // CSV列13: 電話番号 (例: 0761-21-5112, 電話番号不明)
            $table->string('phone', 30)->nullable()->comment('電話番号');
            
            // システム用フラグ
            $table->boolean('is_registered')->default(false)->comment('システム登録済みフラグ');
            
            $table->timestamps();
            
            // パフォーマンス最適化のためのインデックス（約3万件対応）
            // 新規登録時に「卒業回」と「氏名」で高頻度検索
            $table->index('graduation_term', 'idx_graduation_term');
            $table->index('name', 'idx_name');
            $table->index('kana', 'idx_kana');
            
            // 複合インデックス: 卒業回 + 氏名での照合用
            $table->index(['graduation_term', 'name'], 'idx_term_name');
            
            // 複合インデックス: 卒業回 + フリガナでの照合用
            $table->index(['graduation_term', 'kana'], 'idx_term_kana');
            
            // 登録済みフラグでの絞り込み用
            $table->index('is_registered', 'idx_is_registered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_rosters');
    }
};
