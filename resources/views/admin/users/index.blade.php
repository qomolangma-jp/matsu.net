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
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <small>総登録者数</small>
            <h3>{{ number_format($stats['total']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>承認済み</small>
            <h3>{{ number_format($stats['approved']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <small>承認待ち</small>
            <h3>{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
            <small>郵送物不達</small>
            <h3>{{ number_format($stats['mail_unreachable']) }}</h3>
        </div>
    </div>
</div>

<!-- 検索フィルター -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel"></i> 絞り込み検索
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" id="filterForm">
            <div class="row g-3">
                <!-- キーワード検索 -->
                <div class="col-md-4">
                    <label class="form-label">キーワード（氏名・カナ）</label>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           value="{{ $filters['search'] }}" 
                           placeholder="山田 太郎">
                </div>

                <!-- 卒業年度 -->
                <div class="col-md-2">
                    <label class="form-label">卒業年度</label>
                    <select class="form-select" name="graduation_year">
                        <option value="">すべて</option>
                        @foreach($graduationYears as $year)
                            <option value="{{ $year }}" {{ $filters['graduation_year'] == $year ? 'selected' : '' }}>
                                {{ $year }}年（{{ $year - 1947 }}回期）
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- カテゴリー -->
                <div class="col-md-2">
                    <label class="form-label">カテゴリー</label>
                    <select class="form-select" name="category_id">
                        <option value="">すべて</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 権限 -->
                <div class="col-md-2">
                    <label class="form-label">権限</label>
                    <select class="form-select" name="role">
                        <option value="">すべて</option>
                        <option value="general" {{ $filters['role'] === 'general' ? 'selected' : '' }}>一般ユーザー</option>
                        <option value="year_admin" {{ $filters['role'] === 'year_admin' ? 'selected' : '' }}>学年管理者</option>
                        <option value="master_admin" {{ $filters['role'] === 'master_admin' ? 'selected' : '' }}>マスター管理者</option>
                    </select>
                </div>

                <!-- 承認ステータス -->
                <div class="col-md-2">
                    <label class="form-label">承認ステータス</label>
                    <select class="form-select" name="approval_status">
                        <option value="">すべて</option>
                        <option value="pending" {{ $filters['approval_status'] === 'pending' ? 'selected' : '' }}>承認待ち</option>
                        <option value="approved" {{ $filters['approval_status'] === 'approved' ? 'selected' : '' }}>承認済み</option>
                        <option value="rejected" {{ $filters['approval_status'] === 'rejected' ? 'selected' : '' }}>却下</option>
                    </select>
                </div>

                <!-- 郵送物不達 -->
                <div class="col-md-2">
                    <label class="form-label">郵送物不達</label>
                    <select class="form-select" name="mail_unreachable">
                        <option value="">すべて</option>
                        <option value="1" {{ $filters['mail_unreachable'] === '1' ? 'selected' : '' }}>不達</option>
                        <option value="0" {{ $filters['mail_unreachable'] === '0' ? 'selected' : '' }}>正常</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> 検索
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
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
                        <th style="width: 60px;">ID</th>
                        <th>
                            <a href="?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'graduation_year', 'sort_order' => $sortBy === 'graduation_year' && $sortOrder === 'desc' ? 'asc' : 'desc'])) }}" class="text-decoration-none text-dark">
                                卒業年度
                                @if($sortBy === 'graduation_year')
                                    <i class="bi bi-arrow-{{ $sortOrder === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>氏名</th>
                        <th>メールアドレス</th>
                        <th>地区会</th>
                        <th>権限</th>
                        <th>郵送物</th>
                        <th>承認ステータス</th>
                        <th style="width: 150px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <strong>{{ $user->graduation_year }}年</strong><br>
                                <small class="text-muted">{{ $user->graduation_year - 1947 }}回期</small>
                            </td>
                            <td>
                                <strong>{{ $user->last_name }} {{ $user->first_name }}</strong><br>
                                <small class="text-muted">{{ $user->last_name_kana }} {{ $user->first_name_kana }}</small>
                            </td>
                            <td>
                                @if($user->email)
                                    <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($user->categories->count() > 0)
                                    @foreach($user->categories as $category)
                                        <span class="badge bg-info me-1">{{ $category->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($user->role === 'master_admin')
                                    <span class="badge bg-danger">マスター管理者</span>
                                @elseif($user->role === 'year_admin')
                                    <span class="badge bg-primary">学年管理者</span>
                                @else
                                    <span class="badge bg-secondary">一般ユーザー</span>
                                @endif
                            </td>
                            <td>
                                @if($user->mail_unreachable)
                                    <span class="badge bg-danger">不達</span>
                                @else
                                    <span class="badge bg-success">正常</span>
                                @endif
                            </td>
                            <td>
                                @if($user->approval_status === 'approved')
                                    <span class="badge bg-success">承認済み</span>
                                @elseif($user->approval_status === 'pending')
                                    <span class="badge bg-warning text-dark">承認待ち</span>
                                @else
                                    <span class="badge bg-secondary">却下</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="btn btn-outline-primary" 
                                       title="編集">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    @if($user->approval_status === 'pending')
                                        <button type="button" 
                                                class="btn btn-success" 
                                                onclick="approveUser({{ $user->id }}, '{{ $user->full_name }}')"
                                                title="承認">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger" 
                                                onclick="rejectUser({{ $user->id }}, '{{ $user->full_name }}')"
                                                title="却下">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif

                                    <button type="button"
                                            class="btn btn-outline-danger"
                                            title="削除"
                                            onclick="deleteUser({{ $user->id }}, '{{ $user->full_name }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
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
