@extends('layouts.admin')

@section('title', 'ダッシュボード - 松.net')
@section('page-title', 'ダッシュボード')

@section('top-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people"></i> 名簿一覧
    </a>
@endsection

@section('content')

{{-- 統計カード --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-md-4">
        <div class="stats-card">
            <small>総ユーザー数</small>
            <h3>{{ number_format($stats['total_users']) }}</h3>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>承認済み</small>
            <h3>{{ number_format($stats['approved_users']) }}</h3>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <small>承認待ち</small>
            <h3>{{ number_format($stats['pending_users']) }}</h3>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- 未承認ユーザー --}}
    @if($pendingUsers->count() > 0)
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-exclamation"></i> 承認待ちユーザー（{{ $pendingUsers->count() }}名）</span>
                <a href="{{ route('admin.users.index', ['approval_status' => 'pending']) }}" class="btn btn-sm btn-dark">
                    一覧を見る
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>氏名</th>
                                <th>年度</th>
                                <th class="d-none d-md-table-cell">登録日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingUsers as $user)
                            <tr>
                                <td>{{ $user->last_name }} {{ $user->first_name }}</td>
                                <td>{{ $user->graduation_year }}年<br><small class="text-muted">{{ $user->graduation_year - 1947 }}回</small></td>
                                <td class="d-none d-md-table-cell">
                                    <small>{{ $user->created_at->format('Y/m/d H:i') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i> 編集
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 最新登録ユーザー --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-plus"></i> 最新登録ユーザー</span>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">一覧</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($latestUsers as $user)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-bold small">{{ $user->last_name }} {{ $user->first_name }}</div>
                            <small class="text-muted">{{ $user->graduation_year }}年（{{ $user->graduation_year - 1947 }}回）</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($user->approval_status === 'approved')
                                <span class="badge bg-success">承認済</span>
                            @elseif($user->approval_status === 'pending')
                                <span class="badge bg-warning text-dark">待ち</span>
                            @else
                                <span class="badge bg-secondary">却下</span>
                            @endif
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary btn-sm py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3">ユーザーがいません</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- 最新イベント --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event"></i> 最新イベント</span>
                <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-primary">一覧</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($latestEvents as $event)
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold small">{{ $event->title }}</div>
                                <small class="text-muted">
                                    @if($event->event_date)
                                        {{ $event->event_date->format('Y/m/d') }}
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-2">
                                @if($event->is_published)
                                    <span class="badge bg-success">公開</span>
                                @else
                                    <span class="badge bg-secondary">非公開</span>
                                @endif
                                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-outline-secondary btn-sm py-0">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3">イベントがありません</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- 最新ニュース --}}
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-newspaper"></i> 最新ニュース</span>
                <a href="{{ route('admin.news.index') }}" class="btn btn-sm btn-outline-primary">一覧</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($latestNews as $news)
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold small">{{ $news->title }}</div>
                                <small class="text-muted">{{ $news->created_at->format('Y/m/d') }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-2">
                                @if($news->is_published ?? true)
                                    <span class="badge bg-success">公開</span>
                                @else
                                    <span class="badge bg-secondary">非公開</span>
                                @endif
                                <a href="{{ route('admin.news.edit', $news) }}" class="btn btn-outline-secondary btn-sm py-0">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3">ニュースがありません</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

</div>

@endsection
