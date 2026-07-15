@extends('layouts.admin')

@section('title', '名簿管理 - 松高.net')
@section('page-title', '名簿管理')

@section('top-actions')
    <a href="{{ route('admin.users.export', request()->all()) }}" class="btn btn-success">
        <i class="bi bi-download"></i> CSV ダウンロード
    </a>
@endsection

@section('content')
<!-- 統計情報 -->
<div class="row g-2 mb-3 stats-summary">
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
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" name="role">
                        <option value="">権限: すべて</option>
                        <option value="general" {{ $filters['role'] === 'general' ? 'selected' : '' }}>一般ユーザー</option>
                        <option value="year_admin" {{ $filters['role'] === 'year_admin' ? 'selected' : '' }}>学年管理者</option>
                        <option value="master_admin" {{ $filters['role'] === 'master_admin' ? 'selected' : '' }}>マスター管理者</option>
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
        <form id="bulkActionForm" method="POST" action="{{ route('admin.users.bulk-action') }}" class="d-flex gap-2 align-items-center">
            @csrf
            <select name="bulk_action" id="bulkActionSelect" class="form-select form-select-sm" style="width: 180px;">
                <option value="">一括操作を選択</option>
                <option value="approve">承認済みに変更</option>
                <option value="set_role_year_admin">権限を学年管理者へ変更</option>
                <option value="set_role_general">権限を一般ユーザーへ変更</option>
                <option value="delete">削除</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary" id="bulkActionSubmit">
                実行
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:44px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="selectAllUsers" title="全選択">
                        </th>
                        <th style="width:52px;">ID</th>
                        <th>
                            <a href="?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'graduation_year', 'sort_order' => $sortBy === 'graduation_year' && $sortOrder === 'desc' ? 'asc' : 'desc'])) }}" class="text-decoration-none text-dark">
                                年度
                                @if($sortBy === 'graduation_year')
                                    <i class="bi bi-arrow-{{ $sortOrder === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th style="width:130px;">権限/承認</th>
                        <th>氏名</th>
                        <th style="width:80px;">性別</th>
                        <th style="width:90px;">郵送</th>
                        <th style="width:170px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $isMasterLockedForYearAdmin = Auth::user()->role === 'year_admin' && $user->role === 'master_admin';
                        @endphp
                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox"
                                       class="form-check-input row-user-checkbox"
                                       name="selected_user_ids[]"
                                       value="{{ $user->id }}"
                                       form="bulkActionForm"
                                       @if($isMasterLockedForYearAdmin) disabled @endif>
                            </td>
                            <td class="align-middle">{{ $user->id }}</td>
                            <td style="white-space:nowrap;" class="align-middle">
                                <strong>{{ $user->graduation_year }}</strong><br>
                                <small class="text-muted">{{ $user->graduation_year - 1947 }}回</small>
                            </td>
                            <td class="align-middle" style="white-space:nowrap;">
                                @if($user->role !== 'general')
                                    @if($user->role === 'master_admin')
                                        <span class="badge bg-danger">マスター</span>
                                    @elseif($user->role === 'year_admin')
                                        <span class="badge bg-primary">学年管理者</span>
                                    @endif
                                    <br>
                                @endif

                                @if($user->approval_status === 'approved')
                                    <span class="badge bg-success">承認済</span>
                                @elseif($user->approval_status === 'pending')
                                    <span class="badge bg-warning text-dark">待ち</span>
                                @else
                                    <span class="badge bg-secondary">却下</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;" class="align-middle">
                                <strong>{{ $user->last_name }} {{ $user->first_name }}</strong><br>
                                <small class="text-muted">{{ $user->last_name_kana }} {{ $user->first_name_kana }}</small>
                            </td>
                            <td class="align-middle" style="white-space:nowrap;">
                                @if($user->gender === 'male')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">男性</span>
                                @elseif($user->gender === 'female')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">女性</span>
                                @elseif($user->gender === 'other')
                                    <span class="badge bg-secondary">その他</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle" style="white-space:nowrap;">
                                @if($user->mail_unreachable)
                                    <span class="badge bg-danger">不達</span>
                                @else
                                    <span class="badge bg-success">正常</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <select class="form-select form-select-sm"
                                        onchange="handleRowAction(this, {{ $user->id }}, @js($user->full_name))"
                                        @if($isMasterLockedForYearAdmin) disabled @endif>
                                    @if($isMasterLockedForYearAdmin)
                                        <option value="">操作不可</option>
                                    @else
                                        <option value="">操作を選択</option>
                                        <option value="edit">編集</option>
                                        @if($user->approval_status !== 'approved')
                                            <option value="approve">承認</option>
                                        @endif
                                        <option value="delete">削除</option>
                                    @endif
                                </select>
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
const bulkActionForm = document.getElementById('bulkActionForm');
const bulkActionSelect = document.getElementById('bulkActionSelect');
const selectAllUsers = document.getElementById('selectAllUsers');

if (selectAllUsers) {
    selectAllUsers.addEventListener('change', function () {
        document.querySelectorAll('.row-user-checkbox:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = selectAllUsers.checked;
        });
    });
}

document.querySelectorAll('.row-user-checkbox').forEach((checkbox) => {
    checkbox.addEventListener('change', function () {
        const rowCheckboxes = document.querySelectorAll('.row-user-checkbox:not(:disabled)');
        const checkedCount = document.querySelectorAll('.row-user-checkbox:not(:disabled):checked').length;
        if (selectAllUsers) {
            selectAllUsers.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
            selectAllUsers.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        }
    });
});

if (bulkActionForm) {
    bulkActionForm.addEventListener('submit', function (event) {
        const selectedUsers = document.querySelectorAll('.row-user-checkbox:not(:disabled):checked');

        if (!bulkActionSelect.value) {
            event.preventDefault();
            alert('一括操作を選択してください。');
            return;
        }

        if (selectedUsers.length === 0) {
            event.preventDefault();
            alert('対象ユーザーを選択してください。');
            return;
        }

        let message = `${selectedUsers.length}名に対して処理を実行します。よろしいですか？`;
        if (bulkActionSelect.value === 'approve') {
            message = `${selectedUsers.length}名を承認済みに変更します。よろしいですか？`;
        }
        if (bulkActionSelect.value === 'delete') {
            message = `${selectedUsers.length}名を削除します。\nこの操作は取り消せません。よろしいですか？`;
        }
        if (bulkActionSelect.value === 'set_role_year_admin') {
            message = `${selectedUsers.length}名の権限を学年管理者へ変更します。よろしいですか？`;
        }
        if (bulkActionSelect.value === 'set_role_general') {
            message = `${selectedUsers.length}名の権限を一般ユーザーへ変更します。よろしいですか？`;
        }

        if (!confirm(message)) {
            event.preventDefault();
        }
    });
}

function handleRowAction(selectElement, userId, userName) {
    const action = selectElement.value;
    if (!action) {
        return;
    }

    if (action === 'edit') {
        window.location.href = `/admin/users/${userId}/edit`;
        return;
    }

    if (action === 'approve') {
        approveUser(userId, userName);
    }

    if (action === 'delete') {
        deleteUser(userId, userName);
    }

    selectElement.value = '';
}

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
