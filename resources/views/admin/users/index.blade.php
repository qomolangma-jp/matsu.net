@extends('layouts.admin')

@section('title', '名簿管理 - 松.net')
@section('page-title', '名簿管理')

@section('top-actions')
    <a href="{{ route('admin.users.export', request()->all()) }}" class="btn btn-success">
        <i class="bi bi-download"></i> CSV ダウンロード
    </a>
@endsection

@section('content')
<!-- 統計情報 -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <small>総登録者数</small>
            <h3>{{ number_format($stats['total']) }}</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>承認済み</small>
            <h3>{{ number_format($stats['approved']) }}</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <small>承認待ち</small>
            <h3>{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
            <small>郵送物不達</small>
            <h3>{{ number_format($stats['mail_unreachable']) }}</h3>
        </div>
    </div>
</div>

<!-- 検索フィルター -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel"></i> 絞り込み検索
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" id="filterForm">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <input type="text" class="form-control form-control-sm" name="search"
                           value="{{ $filters['search'] }}" placeholder="氏名・カナ">
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" name="graduation_year">
                        <option value="">年度: すべて</option>
                        @foreach($graduationYears as $year)
                            <option value="{{ $year }}" {{ $filters['graduation_year'] == $year ? 'selected' : '' }}>
                                {{ $year }}年（{{ $year - 1947 }}回期）
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" name="category_id">
                        <option value="">地区会: すべて</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" name="approval_status">
                        <option value="">承認: すべて</option>
                        <option value="pending" {{ $filters['approval_status'] === 'pending' ? 'selected' : '' }}>承認待ち</option>
                        <option value="approved" {{ $filters['approval_status'] === 'approved' ? 'selected' : '' }}>承認済み</option>
                        <option value="rejected" {{ $filters['approval_status'] === 'rejected' ? 'selected' : '' }}>却下</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" name="mail_unreachable">
                        <option value="">郵送: すべて</option>
                        <option value="1" {{ $filters['mail_unreachable'] === '1' ? 'selected' : '' }}>不達</option>
                        <option value="0" {{ $filters['mail_unreachable'] === '0' ? 'selected' : '' }}>正常</option>
                    </select>
                </div>
            </div>
            <div class="mt-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> 検索
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> リセット
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 名簿一覧 -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-ul"></i> 名簿一覧
            <span class="badge bg-secondary ms-2">{{ $users->total() }}件</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">ID</th>
                        <th>
                            <a href="?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'graduation_year', 'sort_order' => $sortBy === 'graduation_year' && $sortOrder === 'desc' ? 'asc' : 'desc'])) }}" class="text-decoration-none text-dark">
                                年度
                                @if($sortBy === 'graduation_year')
                                    <i class="bi bi-arrow-{{ $sortOrder === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>氏名</th>
                        <th class="d-none d-md-table-cell">メールアドレス</th>
                        <th class="d-none d-lg-table-cell">地区会</th>
                        <th class="d-none d-lg-table-cell">権限</th>
                        <th class="d-none d-md-table-cell">郵送</th>
                        <th>承認</th>
                        <th style="width:80px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td style="white-space:nowrap;">
                                <strong>{{ $user->graduation_year }}</strong><br>
                                <small class="text-muted">{{ $user->graduation_year - 1947 }}回</small>
                            </td>
                            <td style="white-space:nowrap;">
                                <strong>{{ $user->last_name }} {{ $user->first_name }}</strong><br>
                                <small class="text-muted">{{ $user->last_name_kana }} {{ $user->first_name_kana }}</small>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @if($user->email)
                                    <a href="mailto:{{ $user->email }}" class="small">{{ $user->email }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                @if($user->categories->count() > 0)
                                    @foreach($user->categories as $category)
                                        <span class="badge bg-info me-1">{{ $category->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                @if($user->role === 'master_admin')
                                    <span class="badge bg-danger">マスター</span>
                                @elseif($user->role === 'year_admin')
                                    <span class="badge bg-primary">学年</span>
                                @else
                                    <span class="badge bg-secondary">一般</span>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                @if($user->mail_unreachable)
                                    <span class="badge bg-danger">不達</span>
                                @else
                                    <span class="badge bg-success">正常</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if($user->approval_status === 'approved')
                                    <span class="badge bg-success">承認済</span>
                                @elseif($user->approval_status === 'pending')
                                    <span class="badge bg-warning text-dark">待ち</span>
                                @else
                                    <span class="badge bg-secondary">却下</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="btn btn-outline-primary btn-sm"
                                       title="編集">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if($user->approval_status === 'pending')
                                        <button type="button"
                                                class="btn btn-success btn-sm"
                                                onclick="approveUser({{ $user->id }}, '{{ $user->full_name }}')"
                                                title="承認">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-sm"
                                                onclick="rejectUser({{ $user->id }}, '{{ $user->full_name }}')"
                                                title="却下">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif

                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            title="削除"
                                            onclick="deleteUser({{ $user->id }}, '{{ $user->full_name }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                該当するユーザーが見つかりませんでした。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($users->hasPages())
        <div class="card-footer d-flex justify-content-center">
            <nav aria-label="ユーザー一覧ページネーション">
                {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</div>

<!-- 承認フォーム（非表示） -->
<form id="approveForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="note" id="approveNote">
</form>

<!-- 却下フォーム（非表示） -->
<form id="rejectForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="note" id="rejectNote">
</form>

<!-- 削除フォーム（非表示） -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function approveUser(userId, userName) {
    if (confirm(`${userName}さんを承認しますか？`)) {
        const form = document.getElementById('approveForm');
        form.action = `/admin/users/${userId}/approve`;
        form.submit();
    }
}

function rejectUser(userId, userName) {
    const note = prompt(`${userName}さんの申請を却下します。\n理由を入力してください（任意）:`);
    if (note !== null) {
        const form = document.getElementById('rejectForm');
        document.getElementById('rejectNote').value = note || '管理者により却下';
        form.action = `/admin/users/${userId}/reject`;
        form.submit();
    }
}

function deleteUser(userId, userName) {
    if (confirm(`本当に${userName}さんを削除してもよろしいですか？\nこの操作は取り消せません。`)) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/users/${userId}`;
        form.submit();
    }
}
</script>
@endpush
