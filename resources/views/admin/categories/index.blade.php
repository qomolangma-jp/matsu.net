@extends('layouts.admin')

@section('title', 'カテゴリー管理 - 松.net')
@section('page-title', 'カテゴリー管理')

@section('top-actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> 新規カテゴリー作成
    </a>
@endsection

@section('content')
<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <small>全カテゴリー数</small>
            <h3>{{ number_format($stats['total']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>アクティブ</small>
            <h3>{{ number_format($stats['active']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
            <small>非アクティブ</small>
            <h3>{{ number_format($stats['inactive']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <small>地区会カテゴリー</small>
            <h3>{{ number_format($stats['district']) }}</h3>
        </div>
    </div>
</div>

<!-- 絞り込みフォーム -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel"></i> 絞り込み
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.categories.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">タイプ</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">すべて</option>
                        <option value="district" {{ request('type') === 'district' ? 'selected' : '' }}>地区会</option>
                        <option value="role" {{ request('type') === 'role' ? 'selected' : '' }}>役職</option>
                        <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>その他</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="is_active" class="form-label">有効状態</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="">すべて</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>アクティブ</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>非アクティブ</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search" class="form-label">検索</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control" 
                           placeholder="カテゴリー名またはslugで検索"
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> 検索
                    </button>
                </div>
            </div>

            @if(request()->hasAny(['type', 'is_active', 'search']))
                <div class="mt-3">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> 絞り込みをクリア
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- カテゴリー一覧 -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> カテゴリー一覧（{{ number_format($categories->total()) }} 件）
    </div>
    <div class="card-body p-0">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">カテゴリー名</th>
                            <th style="width: 15%;">Slug</th>
                            <th style="width: 10%;">タイプ</th>
                            <th style="width: 8%;">表示順</th>
                            <th style="width: 8%;">状態</th>
                            <th style="width: 10%;">ユーザー数</th>
                            <th style="width: 12%;">更新日時</th>
                            <th style="width: 12%;" class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    @if($category->description)
                                        <br>
                                        <small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>
                                    @if($category->type === 'district')
                                        <span class="badge bg-primary">地区会</span>
                                    @elseif($category->type === 'role')
                                        <span class="badge bg-info">役職</span>
                                    @else
                                        <span class="badge bg-secondary">その他</span>
                                    @endif
                                </td>
                                <td>{{ $category->display_order }}</td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">アクティブ</span>
                                    @else
                                        <span class="badge bg-secondary">非アクティブ</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($category->users_count) }} 人</span>
                                </td>
                                <td>
                                    <small>{{ $category->updated_at->format('Y/m/d H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.categories.edit', $category) }}" 
                                           class="btn btn-outline-primary"
                                           title="編集">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                onclick="confirmDelete({{ $category->id }}, '{{ $category->name }}', {{ $category->users_count }})"
                                                title="削除">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $category->id }}" 
                                          action="{{ route('admin.categories.destroy', $category) }}" 
                                          method="POST" 
                                          class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <div class="p-3 d-flex justify-content-center">
                <nav aria-label="カテゴリー一覧ページネーション">
                    {{ $categories->onEachSide(1)->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">カテゴリーがありません</p>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> 新規カテゴリー作成
                </a>
            </div>
        @endif
    </div>
</div>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{{ session('success') }}');
        });
    </script>
@endif

<script>
function confirmDelete(categoryId, categoryName, usersCount) {
    let message = `カテゴリー「${categoryName}」を削除してもよろしいですか？`;
    
    if (usersCount > 0) {
        message += `\n\n注意: このカテゴリーには ${usersCount} 人のユーザーが紐づいています。\n削除すると、これらのユーザーから当カテゴリーが解除されます。`;
    }
    
    if (confirm(message)) {
        document.getElementById('delete-form-' + categoryId).submit();
    }
}
</script>
@endsection
