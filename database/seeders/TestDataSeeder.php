<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ReferenceRoster;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // マスター管理者の作成
            User::create([
                'line_id' => 'master_admin_line_001',
                'last_name' => 'マスター',
                'first_name' => '管理者',
                'last_name_kana' => 'マスター',
                'first_name_kana' => 'カンリシャ',
                'birth_date' => '1980-01-01',
                'graduation_year' => 1998,
                'email' => 'master@matsu.localhost',
                'role' => 'master_admin',
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            // 2018年度の学年管理者の作成
            User::create([
                'line_id' => 'year_admin_2018_line_001',
                'last_name' => '学年',
                'first_name' => '管理者',
                'last_name_kana' => 'ガクネン',
                'first_name_kana' => 'カンリシャ',
                'birth_date' => '2000-05-01',
                'graduation_year' => 2018,
                'email' => 'admin2018@matsu.localhost',
                'role' => 'year_admin',
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            // 2019年度の学年管理者の作成
            User::create([
                'line_id' => 'year_admin_2019_line_001',
                'last_name' => '学年',
                'first_name' => '太郎',
                'last_name_kana' => 'ガクネン',
                'first_name_kana' => 'タロウ',
                'birth_date' => '2001-03-15',
                'graduation_year' => 2019,
                'email' => 'admin2019@matsu.localhost',
                'role' => 'year_admin',
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            // 参照名簿データの作成
            $referenceData = [
                // 2018年度
                [
                    'last_name' => '山田',
                    'first_name' => '太郎',
                    'last_name_kana' => 'ヤマダ',
                    'first_name_kana' => 'タロウ',
                    'birth_date' => '2000-04-15',
                    'graduation_year' => 2018,
                    'email' => 'yamada.taro@example.com',
                    'postal_code' => '1234567',
                    'address' => '東京都渋谷区1-2-3',
                    'is_registered' => false,
                ],
                [
                    'last_name' => '佐藤',
                    'first_name' => '花子',
                    'last_name_kana' => 'サトウ',
                    'first_name_kana' => 'ハナコ',
                    'birth_date' => '2000-08-20',
                    'graduation_year' => 2018,
                    'email' => 'sato.hanako@example.com',
                    'postal_code' => '1234567',
                    'address' => '東京都新宿区4-5-6',
                    'is_registered' => false,
                ],
                [
                    'last_name' => '田中',
                    'first_name' => '次郎',
                    'last_name_kana' => 'タナカ',
                    'first_name_kana' => 'ジロウ',
                    'birth_date' => '1999-12-10',
                    'graduation_year' => 2018,
                    'email' => 'tanaka.jiro@example.com',
                    'postal_code' => '9876543',
                    'address' => '大阪府大阪市7-8-9',
                    'is_registered' => false,
                ],
                // 2019年度
                [
                    'last_name' => '鈴木',
                    'first_name' => '一郎',
                    'last_name_kana' => 'スズキ',
                    'first_name_kana' => 'イチロウ',
                    'birth_date' => '2001-01-05',
                    'graduation_year' => 2019,
                    'email' => 'suzuki.ichiro@example.com',
                    'postal_code' => '5551234',
                    'address' => '神奈川県横浜市1-1-1',
                    'is_registered' => false,
                ],
                [
                    'last_name' => '高橋',
                    'first_name' => '美咲',
                    'last_name_kana' => 'タカハシ',
                    'first_name_kana' => 'ミサキ',
                    'birth_date' => '2001-06-25',
                    'graduation_year' => 2019,
                    'email' => 'takahashi.misaki@example.com',
                    'postal_code' => '5551234',
                    'address' => '神奈川県川崎市2-2-2',
                    'is_registered' => false,
                ],
                // 2020年度
                [
                    'last_name' => '伊藤',
                    'first_name' => '健太',
                    'last_name_kana' => 'イトウ',
                    'first_name_kana' => 'ケンタ',
                    'birth_date' => '2002-03-10',
                    'graduation_year' => 2020,
                    'email' => 'ito.kenta@example.com',
                    'postal_code' => '6660001',
                    'address' => '愛知県名古屋市3-3-3',
                    'is_registered' => false,
                ],
                [
                    'last_name' => '渡辺',
                    'first_name' => '由美',
                    'last_name_kana' => 'ワタナベ',
                    'first_name_kana' => 'ユミ',
                    'birth_date' => '2002-09-15',
                    'graduation_year' => 2020,
                    'email' => 'watanabe.yumi@example.com',
                    'postal_code' => '6660001',
                    'address' => '愛知県豊田市4-4-4',
                    'is_registered' => false,
                ],
            ];

            foreach ($referenceData as $data) {
                ReferenceRoster::create($data);
            }

            DB::commit();

            $this->command->info('テストデータの作成が完了しました。');
            $this->command->info('- マスター管理者: master@matsu.localhost');
            $this->command->info('- 2018年度学年管理者: admin2018@matsu.localhost');
            $this->command->info('- 2019年度学年管理者: admin2019@matsu.localhost');
            $this->command->info('- 参照名簿: ' . count($referenceData) . '件');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('エラーが発生しました: ' . $e->getMessage());
        }
    }
}
