@extends('layouts.admin')

@section('title', 'イベント管理 - 松高.net')
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
        {{-- PC向けテーブル表示 --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>タイトル</th>
                        <th>開催日時</th>
                        <th>対象学年</th>
                        <th>出欠状況</th>
                        <th>LINE送信</th>
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
                                @if(isset($event->line_sent_count) && $event->line_sent_count > 0)
                                    <span class="badge bg-success">
                                        <i class="bi bi-line"></i> {{ number_format($event->line_sent_count) }}件
                                    </span>
                                @else
                                    <span class="text-muted small">未送信</span>
                                @endif
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
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                イベントはありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- スマホ向けカード表示 --}}
        <div class="d-md-none">
            @forelse($events as $event)
                <a href="{{ route('admin.events.edit', $event->id) }}" class="text-decoration-none text-dark">
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1 fw-bold">{{ $event->title }}</h6>
                            @if($event->is_published)
                                <span class="badge bg-success">公開中</span>
                            @else
                                <span class="badge bg-warning text-dark">下書き</span>
                            @endif
                        </div>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar3"></i>
                            @if($event->event_date)
                                {{ $event->event_date->format('Y/m/d H:i') }}
                            @else
                                未定
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge {{ $event->graduation_year ? 'bg-info' : 'bg-primary' }}">
                                    {{ $event->target_year_display }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                @php
                                    $attendingCount = $event->attendances()->where('status', 'attending')->count();
                                    $totalCount = $event->attendances()->count();
                                @endphp
                                <i class="bi bi-people"></i>
                                {{ $attendingCount }} / {{ $totalCount }}
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2">イベントはありません。</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($events->hasPages())
        <div class="card-footer">
            {{ $events->links() }}
        </div>
    @endif
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
function deleteEvent(id, title) {
    if (confirm(`「${title}」を削除してもよろしいですか？`)) {
        const form = document.getElementById('delete-form');
        form.action = `/admin/events/${id}`;
        form.submit();
    }
}
</script>
@endpush
