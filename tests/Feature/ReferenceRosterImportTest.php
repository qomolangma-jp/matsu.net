<?php

namespace Tests\Feature;

use App\Models\ReferenceRoster;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

class ReferenceRosterImportTest extends BaseTestCase
{
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
        $this->app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $this->app['config']->set('session.driver', 'array');
        $this->app['config']->set('sanctum.guard', null);
        $this->app['config']->set('app.env', 'testing');

        $this->withoutMiddleware();

        $this->app['db']->purge();

        Schema::dropIfExists('reference_rosters');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('member');
            $table->string('approval_status')->default('pending');
            $table->integer('graduation_year')->nullable();
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name_kana')->nullable();
            $table->string('first_name_kana')->nullable();
            $table->string('gender')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('reference_rosters', function (Blueprint $table) {
            $table->id();
            $table->string('graduation_term', 50);
            $table->string('name', 100);
            $table->string('gender', 10)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('role_1', 100)->nullable();
            $table->string('role_2', 100)->nullable();
            $table->string('former_name', 100)->nullable();
            $table->string('kana', 200)->nullable();
            $table->text('notes')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('address_1', 100)->nullable();
            $table->string('address_2', 100)->nullable();
            $table->string('address_3', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamps();
        });
    }

    public function test_duplicate_rows_are_skipped_during_import(): void
    {
        $user = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'master_admin',
        ]);

        ReferenceRoster::create([
            'graduation_term' => '高校51回期',
            'name' => 'テスト 太郎',
            'gender' => '男',
            'is_registered' => false,
        ]);

        $csv = "卒業回,氏名,性別,状態,役職1,役職2,旧姓,フリガナ,備考,郵便番号,住所1,住所2,住所3,電話番号\n";
        $csv .= "高校51回期,テスト 太郎,男,一般,,,,,,,,,,\n";

        $file = UploadedFile::fake()->createWithContent('reference_rosters.csv', $csv, 'text/csv');

        $response = $this->actingAs($user)
            ->postJson(route('admin.reference_rosters.import'), [
                'csv_file' => $file,
                'truncate' => '0',
            ]);

        $response->assertRedirect(route('admin.reference_rosters.index'));
        $this->assertSame(1, ReferenceRoster::count());
    }

    public function test_import_triggers_auto_approval_and_year_admin_for_matching_user(): void
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin-import@example.com',
            'password' => bcrypt('password'),
            'role' => 'master_admin',
        ]);

        $user = User::create([
            'name' => '既存ユーザー',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'role' => 'general',
            'approval_status' => 'pending',
            'graduation_year' => 2018,
            'last_name' => 'テスト',
            'first_name' => '太郎',
            'last_name_kana' => 'イシタニ',
            'first_name_kana' => 'コウジ',
            'gender' => 'male',
        ]);

        $csv = "卒業回,氏名,性別,状態,役職1,役職2,旧姓,フリガナ,備考,郵便番号,住所1,住所2,住所3,電話番号\n";
        $csv .= "高校51回期,テスト 太郎,男性,一般,常任理事,,,,,,,,,\n";

        $file = UploadedFile::fake()->createWithContent('reference_rosters.csv', $csv, 'text/csv');

        $response = $this->actingAs($admin)
            ->postJson(route('admin.reference_rosters.import'), [
                'csv_file' => $file,
                'truncate' => '0',
            ]);

        $response->assertRedirect(route('admin.reference_rosters.index'));
        $this->assertStringContainsString('自動承認', session('success'));
        $this->assertStringContainsString('学年管理者', session('success'));

        $user->refresh();
        $roster = ReferenceRoster::query()->where('name', 'テスト 太郎')->first();

        $this->assertNotNull($roster);
        $this->assertSame('approved', $user->approval_status);
        $this->assertSame('year_admin', $user->role);
        $this->assertTrue($roster->is_registered);
    }

    public function test_sync_command_approves_existing_user_and_grants_year_admin_for_executive_roster(): void
    {
        $user = User::create([
            'name' => '既存ユーザー',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'role' => 'general',
            'approval_status' => 'pending',
            'graduation_year' => 2018,
            'last_name' => 'テスト',
            'first_name' => '太郎',
            'last_name_kana' => 'イシタニ',
            'first_name_kana' => 'コウジ',
            'gender' => 'male',
        ]);

        $roster = ReferenceRoster::create([
            'graduation_term' => '高校51回期',
            'name' => 'テスト 太郎',
            'gender' => '男性',
            'role_1' => '常任理事',
            'kana' => 'ｲｼﾀﾆ ｺｳｼﾞ',
            'is_registered' => false,
        ]);

        app(\App\Services\ReferenceRosterSyncService::class)->syncExistingUsers();

        $user->refresh();
        $roster->refresh();

        $this->assertSame('approved', $user->approval_status);
        $this->assertSame('year_admin', $user->role);
        $this->assertTrue($roster->is_registered);
    }
}
