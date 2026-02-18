@extends('layouts.admin')

@section('title', '参照名簿管理 - 松.net')
@section('page-title', '参照名簿管理')

@section('top-actions')
    <a href="{{ route('admin.reference_rosters.export', request()->all()) }}" class="btn btn-success">
        <i class="bi bi-download"></i> CSV ダウンロード
    </a>
@endsection

@section('content')
<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <small>総データ件数</small>
            <h3>{{ number_format($stats['total']) }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>システム登録済み</small>
            <h3>{{ number_format($stats['registered']) }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
            <small>未登録</small>
            <h3>{{ number_format($stats['not_registered']) }}</h3>
        </div>
    </div>
</div>

<!-- CSVインポート -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-upload"></i> CSVインポート
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.reference_rosters.import') }}" enctype="multipart/form-data" id="importForm">
            @csrf
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>インポート仕様：</strong>
                <ul class="mb-0 mt-2">
                    <li>CSVファイル形式（UTF-8、SJIS、EUC-JP対応）</li>
                    <li>最大ファイルサイズ: 50MB</li>
                    <li>列構成: 卒業回, 氏名, 性別, 状態, 役職1, 役職2, 旧姓, フリガナ, 備考, 郵便番号, 住所1, 住所2, 住所3, 電話番号</li>
                    <li>1行目はヘッダー行として自動スキップされます</li>
                    <li>既存データを削除する場合は「既存データを削除」にチェック</li>
                </ul>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="csvFile" class="form-label required">CSVファイル</label>
                    <input type="file" 
                           class="form-control @error('csv_file') is-invalid @enderror" 
                           id="csvFile" 
                           name="csv_file" 
                           accept=".csv,.txt"
                           required>
                    @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               role="switch" 
                               id="truncate" 
                               name="truncate" 
                               value="1">
                        <label class="form-check-label text-danger" for="truncate">
                            <i class="bi bi-exclamation-triangle"></i> 既存データを削除してインポート
                        </label>
                    </div>
                    <small class="text-muted">既存の参照名簿データをすべて削除してから新規インポートします</small>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="importBtn">
                    <i class="bi bi-upload"></i> インポート実行
                </button>
                <small class="text-muted ms-3">※ 大量データの場合、処理に時間がかかることがあります</small>
            </div>
        </form>
    </div>
</div>

<!-- 検索フィルター -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel"></i> 絞り込み検索
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reference_rosters.index') }}" id="filterForm">
            <div class="row g-3">
                <!-- 卒業回 -->
                <div class="col-md-3">
                    <label class="form-label">卒業回</label>
                    <select class="form-select" name="graduation_term">
                        <option value="">すべて</option>
                        @foreach($graduationTerms as $term)
                            <option value="{{ $term }}" {{ $filters['graduation_term'] == $term ? 'selected' : '' }}>
                                {{ $term }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 氏名 -->
                <div class="col-md-4">
                    <label class="form-label">氏名（部分一致）</label>
                    <input type="text" 
                           class="form-control" 
                           name="name" 
                           value="{{ $filters['name'] }}" 
                           placeholder="山田 太郎">
                </div>

                <!-- 登録ステータス -->
                <div class="col-md-3">
                    <label class="form-label">登録ステータス</label>
                    <select class="form-select" name="is_registered">
                        <option value="">すべて</option>
                        <option value="1" {{ $filters['is_registered'] === '1' ? 'selected' : '' }}>登録済み</option>
                        <option value="0" {{ $filters['is_registered'] === '0' ? 'selected' : '' }}>未登録</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> 検索
                        </button>
                        <a href="{{ route('admin.reference_rosters.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 名簿一覧 -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-ul"></i> 参照名簿一覧
            <span class="badge bg-secondary ms-2">{{ $rosters->total() }}件</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>
                            <a href="?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'graduation_term', 'sort_order' => $sortBy === 'graduation_term' && $sortOrder === 'desc' ? 'asc' : 'desc'])) }}" class="text-decoration-none text-dark">
                                卒業回
                                @if($sortBy === 'graduation_term')
                                    <i class="bi bi-arrow-{{ $sortOrder === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>氏名</th>
                        <th>フリガナ</th>
                        <th>旧姓</th>
                        <th>住所</th>
                        <th>電話番号</th>
                        <th>登録ステータス</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rosters as $roster)
                        <tr>
                            <td>{{ $roster->id }}</td>
                            <td>
                                <strong>{{ $roster->graduation_term }}</strong>
                            </td>
                            <td>
                                <strong>{{ $roster->name }}</strong>
                                @if($roster->gender)
                                    <span class="badge bg-light text-dark ms-1">{{ $roster->gender }}</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $roster->kana ?? '-' }}</small>
                            </td>
                            <td>
                                @if($roster->former_name)
                                    <span class="badge bg-info">旧{{ $roster->former_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($roster->full_address)
                                    <small>{{ Str::limit($roster->full_address, 30) }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $roster->phone ?? '-' }}</small>
                            </td>
                            <td>
                                @if($roster->is_registered)
                                    <span class="badge bg-success">登録済み</span>
                                @else
                                    <span class="badge bg-secondary">未登録</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                参照名簿データが見つかりませんでした。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($rosters->hasPages())
        <div class="card-footer d-flex justify-content-center">
            <nav aria-label="参照名簿ページネーション">
                {{ $rosters->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// インポートフォーム送信時の確認
document.getElementById('importForm').addEventListener('submit', function(e) {
    const truncate = document.getElementById('truncate').checked;
    const fileInput = document.getElementById('csvFile');
    
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('CSVファイルを選択してください');
        return false;
    }
    
    if (truncate) {
        if (!confirm('⚠️ 既存の参照名簿データをすべて削除してからインポートします。\n本当によろしいですか？')) {
            e.preventDefault();
            return false;
        }
    }
    
    // ボタンを無効化（二重送信防止）
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>インポート中...';
});
</script>
@endpush
