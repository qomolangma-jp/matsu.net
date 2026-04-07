@extends('layouts.admin')

@section('title', 'イベント管理 - 松.net')
@section('page-title', 'イベント管理')

@section('top-actions')
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> 新規作成
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-calendar-event"></i> イベント一覧
            <span class="badge bg-secondary ms-2">{{ $events->total() }}件</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>タイトル</th>
                        <th>開催日時</th>
                        <th>対象学年</th>
                        <th>出欠状況</th>
                        <th>公開</th>
                        <th style="width: 180px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ $event->id }}</td>
                            <td>
                                <strong>{{ $event->title }}</strong><br>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> {{ $event->location ?? '未定' }}
                                </small>
                            </td>
                            <td>
                                @if($event->event_date)
                                    <small>{{ $event->event_date->format('Y/m/d H:i') }}</small>
                                @else
                                    <span class="text-muted">未定</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $event->graduation_year ? 'bg-info' : 'bg-primary' }}">
                                    {{ $event->target_year_display }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $attendingCount = $event->attendances()->where('status', 'attending')->count();
                                    $totalCount = $event->attendances()->count();
                                @endphp
                                <small>
                                    <i class="bi bi-people"></i> 
                                    出席 <strong>{{ $attendingCount }}</strong> / 回答 {{ $totalCount }}
                                </small>
                            </td>
                            <td>
                                @if($event->is_published)
                                    <span class="badge bg-success">公開中</span>
                                @else
                                    <span class="badge bg-warning text-dark">下書き</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.events.show', $event->id) }}" 
                                       class="btn btn-outline-success" 
                                       title="出欠確認">
                                        <i class="bi bi-list-check"></i>
                                    </a>
                                    <a href="{{ route('admin.events.edit', $event->id) }}" 
                                       class="btn btn-outline-primary"
                                       title="編集">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            onclick="deleteEvent({{ $event->id }}, '{{ $event->title }}')"
                                            title="削除">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                イベントがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($events->hasPages())
        <div class="card-footer d-flex justify-content-center">
            <nav aria-label="イベント一覧ページネーション">
                {{ $events->onEachSide(1)->links('pagination::bootstrap-5') }}
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
function deleteEvent(eventId, eventTitle) {
    if (confirm(`「${eventTitle}」を削除しますか？\n出欠データもすべて削除されます。この操作は取り消せません。`)) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/events/${eventId}`;
        form.submit();
    }
}
</script>
@endpush
