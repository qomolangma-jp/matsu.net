<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferenceRoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReferenceRosterController extends Controller
{
    /**
     * 参照名簿一覧画面
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 権限チェック（マスター管理者のみ）
        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // クエリビルダー
        $query = ReferenceRoster::query();

        // 絞り込み条件
        $filters = [
            'graduation_term' => $request->input('graduation_term'),
            'name' => $request->input('name'),
            'is_registered' => $request->input('is_registered'),
        ];

        // フィルタ適用
        if ($filters['graduation_term']) {
            $query->byGraduationTerm($filters['graduation_term']);
        }

        if ($filters['name']) {
            $query->byName($filters['name']);
        }

        if ($filters['is_registered'] !== null && $filters['is_registered'] !== '') {
            if ($filters['is_registered'] === '1') {
                $query->registered();
            } else {
                $query->notRegistered();
            }
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $rosters = $query->paginate(100)->withQueryString();

        // 卒業回一覧（ドロップダウン用）
        $graduationTerms = ReferenceRoster::select('graduation_term')
            ->distinct()
            ->orderBy('graduation_term')
            ->pluck('graduation_term');

        // 統計情報
        $stats = [
            'total' => ReferenceRoster::count(),
            'registered' => ReferenceRoster::registered()->count(),
            'not_registered' => ReferenceRoster::notRegistered()->count(),
        ];

        return view('admin.reference_rosters.index', compact(
            'rosters',
            'filters',
            'graduationTerms',
            'stats',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * CSVインポート処理
     */
    public function import(Request $request)
    {
        $user = Auth::user();

        // 権限チェック（マスター管理者のみ）
        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // バリデーション
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 最大50MB
            'truncate' => 'boolean',
        ]);

        $startTime = microtime(true);
        $totalCount = 0;
        $successCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            // テーブルクリア
            if ($request->has('truncate') && $request->input('truncate') == '1') {
                DB::table('reference_rosters')->truncate();
                Log::info('参照名簿テーブルをクリア', ['user_id' => $user->id]);
            }

            // CSVファイルを取得
            $file = $request->file('csv_file');
            $handle = fopen($file->getRealPath(), 'r');

            if ($handle === false) {
                throw new \Exception('CSVファイルを開けません');
            }

            // 文字コード自動検出
            $firstLine = fgets($handle);
            rewind($handle);

            $encoding = mb_detect_encoding($firstLine, ['UTF-8', 'SJIS', 'EUC-JP', 'ASCII'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                stream_filter_prepend($handle, "convert.iconv.{$encoding}/UTF-8");
            }

            // ヘッダー行をスキップ
            fgetcsv($handle);

            // データ処理（チャンク処理）
            $chunk = [];
            $chunkSize = 1000;
            $lineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // 空行をスキップ（すべての列が空またはnull）
                $hasData = false;
                foreach ($row as $cell) {
                    if (isset($cell) && trim($cell) !== '') {
                        $hasData = true;
                        break;
                    }
                }

                if (!$hasData) {
                    continue; // 空行はカウントもスキップ
                }

                $totalCount++;

                // データ行が14列未満の場合はスキップ
                if (count($row) < 14) {
                    $errorCount++;
                    Log::warning("参照名簿CSVインポート: 列数不足", [
                        'line' => $lineNumber,
                        'columns' => count($row),
                    ]);
                    continue;
                }

                // データを配列に変換
                $data = $this->parseRow($row);

                if ($data) {
                    $chunk[] = $data;

                    // チャンクサイズに達したらバルクインサート
                    if (count($chunk) >= $chunkSize) {
                        DB::table('reference_rosters')->insert($chunk);
                        $successCount += count($chunk);
                        $chunk = [];
                    }
                }
            }

            // 残りのチャンクをインサート
            if (!empty($chunk)) {
                DB::table('reference_rosters')->insert($chunk);
                $successCount += count($chunk);
            }

            fclose($handle);

            DB::commit();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info('参照名簿CSVインポート完了', [
                'user_id' => $user->id,
                'total' => $totalCount,
                'success' => $successCount,
                'error' => $errorCount,
                'duration' => $duration,
            ]);

            return redirect()
                ->route('admin.reference_rosters.index')
                ->with('success', "CSVインポート完了: {$successCount}件を登録しました（処理時間: {$duration}秒）");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('参照名簿CSVインポートエラー', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'インポートに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * CSV行をパースしてデータ配列に変換
     */
    private function parseRow(array $row): array
    {
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
    }

    /**
     * 文字列のクリーンアップ
     */
    private function cleanString(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = trim($value);
        return $cleaned === '' ? null : $cleaned;
    }

    /**
     * CSVエクスポート
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // 権限チェック（マスター管理者のみ）
        if ($user->role !== 'master_admin') {
            abort(403, 'マスター管理者のみアクセス可能です。');
        }

        // クエリビルダー
        $query = ReferenceRoster::query();

        // フィルタ適用（一覧と同じ）
        if ($request->input('graduation_term')) {
            $query->byGraduationTerm($request->input('graduation_term'));
        }

        if ($request->input('name')) {
            $query->byName($request->input('name'));
        }

        if ($request->input('is_registered') !== null && $request->input('is_registered') !== '') {
            if ($request->input('is_registered') === '1') {
                $query->registered();
            } else {
                $query->notRegistered();
            }
        }

        $rosters = $query->orderBy('id')->get();

        // CSV生成
        $filename = 'reference_rosters_' . date('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($rosters) {
            $file = fopen('php://output', 'w');

            // BOM追加（Excel対応）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // ヘッダー行
            fputcsv($file, [
                '卒業回',
                '氏名',
                '性別',
                '状態',
                '役職1',
                '役職2',
                '旧姓',
                'フリガナ',
                '備考',
                '郵便番号',
                '住所1',
                '住所2',
                '住所3',
                '電話番号',
                '登録済み',
            ]);

            // データ行
            foreach ($rosters as $roster) {
                fputcsv($file, [
                    $roster->graduation_term,
                    $roster->name,
                    $roster->gender,
                    $roster->status,
                    $roster->role_1,
                    $roster->role_2,
                    $roster->former_name,
                    $roster->kana,
                    $roster->notes,
                    $roster->postal_code,
                    $roster->address_1,
                    $roster->address_2,
                    $roster->address_3,
                    $roster->phone,
                    $roster->is_registered ? '登録済み' : '未登録',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
