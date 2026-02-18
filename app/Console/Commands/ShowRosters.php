<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReferenceRoster;

class ShowRosters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosters:show 
                            {--limit=5 : 表示件数}
                            {--term= : 卒業回で絞り込み}
                            {--name= : 氏名で絞り込み}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'インポートされた参照名簿データを確認';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = ReferenceRoster::query();
        
        // 絞り込み
        if ($term = $this->option('term')) {
            $query->where('graduation_term', 'LIKE', "%{$term}%");
        }
        
        if ($name = $this->option('name')) {
            $query->where('name', 'LIKE', "%{$name}%");
        }
        
        // 総件数
        $total = ReferenceRoster::count();
        $filtered = $query->count();
        
        $this->info("📊 参照名簿データ");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("総件数: " . number_format($total) . "件");
        
        if ($term || $name) {
            $this->info("絞り込み結果: " . number_format($filtered) . "件");
        }
        
        // データ取得
        $limit = (int) $this->option('limit');
        $rosters = $query->limit($limit)->get();
        
        if ($rosters->isEmpty()) {
            $this->warn("データが見つかりません");
            return 0;
        }
        
        // テーブル表示
        $this->newLine();
        $this->table(
            ['ID', '卒業回', '氏名', '性別', 'フリガナ', '郵便番号', '住所', '電話番号', '登録済'],
            $rosters->map(function ($roster) {
                return [
                    $roster->id,
                    $roster->graduation_term,
                    $roster->name,
                    $roster->gender ?? '-',
                    $roster->kana ?? '-',
                    $roster->postal_code ?? '-',
                    $roster->full_address ?? '-',
                    $roster->phone ?? '-',
                    $roster->is_registered ? '✓' : '',
                ];
            })
        );
        
        $this->newLine();
        $this->comment("💡 使い方:");
        $this->comment("  php artisan rosters:show --limit=10");
        $this->comment("  php artisan rosters:show --term=51 --limit=20");
        $this->comment("  php artisan rosters:show --name=相河");
        
        return 0;
    }
}
