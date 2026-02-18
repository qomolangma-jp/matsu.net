<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportRosters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:rosters 
                            {file? : CSVファイルのパス（デフォルト: storage/app/rosters.csv）}
                            {--chunk=1000 : 一度に処理する行数}
                            {--truncate : インポート前にテーブルをクリア}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '参照名簿CSVファイルをreference_rostersテーブルにインポート（約3万件対応）';

    /**
     * 処理件数
     */
    private int $totalCount = 0;
    private int $successCount = 0;
    private int $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        // CSVファイルパスの取得
        $filePath = $this->argument('file') ?? 'rosters.csv';
        $fullPath = storage_path('app/' . $filePath);
        
        // ファイル存在チェック
        if (!file_exists($fullPath)) {
            $this->error("❌ CSVファイルが見つかりません: {$fullPath}");
            $this->info("💡 ファイルを storage/app/rosters.csv に配置してください。");
            return 1;
        }
        
        $this->info("📂 CSVファイル: {$fullPath}");
        $this->info("📊 ファイルサイズ: " . $this->formatBytes(filesize($fullPath)));
        
        // テーブルクリア確認
        if ($this->option('truncate')) {
            if ($this->confirm('⚠️  reference_rostersテーブルの既存データを削除しますか？', true)) {
                DB::table('reference_rosters')->truncate();
                $this->info("🗑️  テーブルをクリアしました。");
            }
        }
        
        // チャンクサイズ
        $chunkSize = (int) $this->option('chunk');
        $this->info("⚙️  チャンクサイズ: {$chunkSize}件");
        
        // プログレスバーの準備
        $this->newLine();
        $this->info("🚀 インポートを開始します...");
        
        try {
            // CSVファイルを開く
            $handle = fopen($fullPath, 'r');
            
            if ($handle === false) {
                throw new \Exception("CSVファイルを開けません");
            }
            
            // 文字コードを自動検出して変換
            // ※最初の1行を読んで文字コード判定
            $firstLine = fgets($handle);
            rewind($handle); // ファイルポインタを先頭に戻す
            
            $encoding = mb_detect_encoding($firstLine, ['UTF-8', 'SJIS', 'EUC-JP', 'ASCII'], true);
            
            if ($encoding && $encoding !== 'UTF-8') {
                stream_filter_prepend($handle, "convert.iconv.{$encoding}/UTF-8");
                $this->comment("🔤 文字コード: {$encoding} → UTF-8 に変換");
            } else {
                $this->comment("🔤 文字コード: UTF-8");
            }
            
            // ヘッダー行をスキップ
            $header = fgetcsv($handle);
            $this->comment("📋 ヘッダー: " . implode(', ', array_slice($header, 0, 5)) . '...');
            
            // データ処理
            $chunk = [];
            $lineNumber = 1; // ヘッダーの次の行から
            
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                $this->totalCount++;
                
                // 空行をスキップ
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // データ行が14列未満の場合は警告
                if (count($row) < 14) {
                    $this->warn("⚠️  行{$lineNumber}: 列数が不足しています（" . count($row) . "列）");
                    $this->errorCount++;
                    continue;
                }
                
                // データを配列に変換
                $data = $this->parseRow($row, $lineNumber);
                
                if ($data) {
                    $chunk[] = $data;
                    
                    // チャンクサイズに達したらバルクインサート
                    if (count($chunk) >= $chunkSize) {
                        $this->insertChunk($chunk);
                        $chunk = [];
                        
                        // 進捗表示
                        $this->info("✅ {$this->successCount} 件インポート完了...");
                    }
                }
            }
            
            // 残りのチャンクをインサート
            if (!empty($chunk)) {
                $this->insertChunk($chunk);
            }
            
            fclose($handle);
            
            // 結果表示
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✨ インポート完了");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->table(
                ['項目', '件数'],
                [
                    ['処理行数', number_format($this->totalCount)],
                    ['成功', number_format($this->successCount)],
                    ['エラー', number_format($this->errorCount)],
                    ['処理時間', "{$duration}秒"],
                    ['処理速度', number_format($this->totalCount / $duration) . '件/秒'],
                ]
            );
            
            // データ確認
            $count = DB::table('reference_rosters')->count();
            $this->info("📊 テーブル総件数: " . number_format($count));
            
            Log::info('参照名簿CSVインポート完了', [
                'total' => $this->totalCount,
                'success' => $this->successCount,
                'error' => $this->errorCount,
                'duration' => $duration,
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ エラーが発生しました: " . $e->getMessage());
            Log::error('参照名簿CSVインポートエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
    
    /**
     * CSV行をパースしてデータ配列に変換
     */
    private function parseRow(array $row, int $lineNumber): ?array
    {
        try {
            return [
                'graduation_term' => $this->cleanString($row[0] ?? ''),
                'name' => $this->cleanString($row[1] ?? ''),
                'gender' => $this->cleanString($row[2] ?? null),
                'status' => $this->cleanString($row[3] ?? null),
                'role_1' => $this->cleanString($row[4] ?? null),
                'role_2' => $this->cleanString($row[5] ?? null),
                'former_name' => $this->cleanString($row[6] ?? null),
                'kana' => $this->cleanString($row[7] ?? null),
                'notes' => $this->cleanString($row[8] ?? null),
                'postal_code' => $this->cleanString($row[9] ?? null),
                'address_1' => $this->cleanString($row[10] ?? null),
                'address_2' => $this->cleanString($row[11] ?? null),
                'address_3' => $this->cleanString($row[12] ?? null),
                'phone' => $this->cleanString($row[13] ?? null),
                'is_registered' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } catch (\Exception $e) {
            $this->warn("⚠️  行{$lineNumber}: パースエラー - " . $e->getMessage());
            $this->errorCount++;
            return null;
        }
    }
    
    /**
     * 文字列のクリーンアップ
     */
    private function cleanString(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // trim + 空文字の場合はnull
        $cleaned = trim($value);
        return $cleaned === '' ? null : $cleaned;
    }
    
    /**
     * チャンクをバルクインサート
     */
    private function insertChunk(array $chunk): void
    {
        try {
            DB::table('reference_rosters')->insert($chunk);
            $this->successCount += count($chunk);
        } catch (\Exception $e) {
            $this->error("❌ インサートエラー: " . $e->getMessage());
            $this->errorCount += count($chunk);
            Log::error('バルクインサートエラー', [
                'error' => $e->getMessage(),
                'chunk_size' => count($chunk),
            ]);
        }
    }
    
    /**
     * ファイルサイズを読みやすい形式に変換
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
