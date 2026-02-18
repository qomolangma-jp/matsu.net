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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('line_id')->unique()->comment('LINE ID（一意）');
            $table->string('last_name')->comment('姓');
            $table->string('first_name')->comment('名');
            $table->string('last_name_kana')->nullable()->comment('姓（カナ）');
            $table->string('first_name_kana')->nullable()->comment('名（カナ）');
            $table->date('birth_date')->nullable()->comment('生年月日');
            $table->year('graduation_year')->comment('卒業年度');
            $table->string('email')->nullable()->comment('メールアドレス');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->text('address')->nullable()->comment('住所');
            $table->boolean('mail_unreachable')->default(false)->comment('郵送物不達フラグ');
            $table->enum('role', ['general', 'year_admin', 'master_admin'])->default('general')->comment('権限（一般/学年管理者/マスター管理者）');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // インデックス
            $table->index('graduation_year');
            $table->index('role');
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
