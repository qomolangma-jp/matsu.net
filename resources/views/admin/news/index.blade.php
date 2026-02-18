@extends('layouts.admin')

@section('title', 'ニュース管理 - 松.net')
@section('page-title', 'ニュース管理')

@section('top-actions')
    <a href="{{ route('admin.news.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> 新規作成
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-newspaper"></i> ニュース一覧
            <span class="badge bg-secondary ms-2">{{ $news->total() }}件</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>タイトル</th>
                        <th>対象学年</th>
                        <th>通知設定</th>
                        <th>公開日時</th>
                        <th>作成者</th>
                        <th style="width: 150px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($news as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>
                                <strong>{{ $item->title }}</strong><br>
                                <small class="text-muted">{{ Str::limit($item->body, 80) }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $item->target_years_display }}</span>
                            </td>
                            <td>
                                @if($item->is_line_notification)
                                    <span class="badge bg-success">LINE送信済</span>
                                @endif
                                @if($item->is_top_display)
                                    <span class="badge bg-primary">TOP掲載</span>
                                @endif
                            </td>
                            <td>
                                @if($item->published_at)
                                    <small>{{ $item->published_at->format('Y/m/d H:i') }}</small>
                                @else
                                    <span class="badge bg-warning text-dark">下書き</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $item->creator?->full_name }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.news.edit', $item->id) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            onclick="deleteNews({{ $item->id }}, '{{ $item->title }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                ニュースがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($news->hasPages())
        <div class="card-footer d-flex justify-content-center">
            <nav aria-label="ニュース一覧ページネーション">
                {{ $news->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</div>

<!-- 削除フォーム（非表示） -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function deleteNews(newsId, newsTitle) {
    if (confirm(`「${newsTitle}」を削除しますか？\nこの操作は取り消せません。`)) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/news/${newsId}`;
        form.submit();
    }
}
</script>
@endpush
