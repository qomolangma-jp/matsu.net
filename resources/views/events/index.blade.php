@extends('layouts.app')

@section('title', 'イベント一覧 - 松.net')

@section('content')
<div class="row">
    <!-- サイドバー -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-person-circle"></i> メニュー
                </h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('mypage.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-house-door"></i> マイページ
                </a>
                <a href="{{ route('mypage.edit') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil-square"></i> プロフィール編集
                </a>
                <a href="{{ route('mypage.password') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-key"></i> パスワード変更
                </a>
                <a href="{{ route('news.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-newspaper"></i> お知らせ一覧
                </a>
                <a href="{{ route('events.index') }}" class="list-group-item list-group-item-action active">
                    <i class="bi bi-calendar-event"></i> イベント一覧
                </a>

                @if(Auth::check() && in_array(Auth::user()->role, ['master_admin', 'year_admin']))
                    <div class="list-group-item bg-light">
                        <small class="text-muted fw-bold">管理メニュー</small>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> 名簿管理
                    </a>
                    <a href="{{ route('admin.events.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-event"></i> イベント管理
                    </a>
                @endif

                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="list-group-item list-group-item-action text-danger">
                    <i class="bi bi-box-arrow-right"></i> ログアウト
                </a>
            </div>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>

    <!-- メインコンテンツ -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event"></i> イベント一覧
                </h5>
            </div>
            <div class="card-body">
                <!-- 期間フィルタ -->
                <div class="mb-4">
                    <a href="{{ route('events.index', ['filter' => 'upcoming']) }}"
                       class="btn btn-sm {{ $filter === 'upcoming' ? 'btn-success' : 'btn-outline-secondary' }} me-1">
                        <i class="bi bi-calendar-check"></i> 今後のイベント
                    </a>
                    <a href="{{ route('events.index', ['filter' => 'past']) }}"
                       class="btn btn-sm {{ $filter === 'past' ? 'btn-success' : 'btn-outline-secondary' }}">
                        <i class="bi bi-calendar-x"></i> 過去のイベント
                    </a>
                </div>

                @if($events->isEmpty())
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        {{ $filter === 'past' ? '過去のイベントはありません。' : '現在、予定されているイベントはありません。' }}
                    </p>
                @else
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        @foreach($events as $event)
                            <div class="col">
                                <a href="{{ route('events.show', $event) }}" class="text-decoration-none">
                                    <div class="card h-100 border hover-shadow">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="card-title text-dark mb-0">{{ $event->title }}</h6>
                                                @if(isset($myAttendances[$event->id]))
                                                    @php $att = $myAttendances[$event->id]; @endphp
                                                    <span class="badge ms-2 flex-shrink-0
                                                        {{ $att->status === 'attending' ? 'bg-success' : ($att->status === 'absent' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                        {{ $att->status_label }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border ms-2 flex-shrink-0">未回答</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <i class="bi bi-calendar3"></i>
                                                {{ $event->event_date->format('Y年m月d日（D）H:i') }}
                                            </div>
                                            @if($event->location)
                                                <div class="small text-muted mb-2">
                                                    <i class="bi bi-geo-alt"></i> {{ $event->location }}
                                                </div>
                                            @endif
                                            @if($event->deadline && $event->deadline->isFuture())
                                                <div class="small text-danger">
                                                    <i class="bi bi-clock"></i> 申込締切：{{ $event->deadline->format('m/d H:i') }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent small text-muted">
                                            <span class="badge" style="background-color: #2c5f2d;">
                                                {{ $event->target_year_display }}
                                            </span>
                                            @if($event->capacity)
                                                <span class="ms-1">定員 {{ $event->capacity }}名</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<style>
.hover-shadow:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: box-shadow 0.2s; }
</style>
@endsection
