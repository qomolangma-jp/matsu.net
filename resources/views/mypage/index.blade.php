@extends('layouts.app')

@section('title', 'マイページ - 松.net')

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
                <a href="{{ route('mypage.index') }}" class="list-group-item list-group-item-action active">
                    <i class="bi bi-house-door"></i> マイページ
                </a>
                <a href="{{ route('mypage.edit') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil-square"></i> プロフィール編集
                </a>
                <a href="{{ route('news.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-newspaper"></i> お知らせ一覧
                </a>
                <a href="{{ route('events.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-calendar-event"></i> イベント一覧
                </a>
                
                @if(Auth::check() && in_array(Auth::user()->role, ['master_admin', 'year_admin']))
                    <div class="list-group-item bg-light">
                        <small class="text-muted fw-bold">管理メニュー</small>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> 名簿管理
                    </a>
                    @if(Auth::user()->role === 'master_admin')
                        <a href="{{ route('admin.reference_rosters.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-database"></i> 参照名簿
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-tags"></i> カテゴリー管理
                        </a>
                    @endif
                    <a href="{{ route('admin.news.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-newspaper"></i> ニュース管理
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
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-badge"></i> プロフィール
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-person"></i> 氏名
                    </div>
                    <div class="col-md-9">
                        <strong>{{ $user->full_name }}</strong>
                    </div>
                </div>

                @if($user->full_name_kana)
                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-alphabet"></i> フリガナ
                    </div>
                    <div class="col-md-9">
                        {{ $user->full_name_kana }}
                    </div>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-calendar3"></i> 生年月日
                    </div>
                    <div class="col-md-9">
                        {{ $user->birth_date ? $user->birth_date->format('Y年m月d日') : '未登録' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-mortarboard"></i> 卒業年度
                    </div>
                    <div class="col-md-9">
                        {{ $user->graduation_year }}年
                    </div>
                </div>

                @if($user->email)
                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-envelope"></i> メール
                    </div>
                    <div class="col-md-9">
                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                    </div>
                </div>
                @endif

                @if($user->phone)
                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-telephone"></i> 電話番号
                    </div>
                    <div class="col-md-9">
                        {{ $user->phone }}
                    </div>
                </div>
                @endif

                @if($user->postal_code || $user->address)
                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-geo-alt"></i> 住所
                    </div>
                    <div class="col-md-9">
                        @if($user->postal_code)
                            〒{{ $user->postal_code }}<br>
                        @endif
                        {{ $user->address }}
                    </div>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-shield-check"></i> 承認状態
                    </div>
                    <div class="col-md-9">
                        @if($user->approval_status === 'approved')
                            <span class="badge bg-success">承認済み</span>
                        @elseif($user->approval_status === 'pending')
                            <span class="badge bg-warning text-dark">承認待ち</span>
                        @else
                            <span class="badge bg-secondary">{{ $user->approval_status }}</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3 text-muted">
                        <i class="bi bi-award"></i> 権限
                    </div>
                    <div class="col-md-9">
                        @if($user->role === 'master_admin')
                            <span class="badge bg-danger">マスター管理者</span>
                        @elseif($user->role === 'year_admin')
                            <span class="badge bg-warning">学年管理者</span>
                        @else
                            <span class="badge bg-secondary">一般ユーザー</span>
                        @endif
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="{{ route('mypage.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> プロフィールを編集
                    </a>
                </div>
            </div>
        </div>

        <!-- お知らせ・イベント情報 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-newspaper"></i> 最新のお知らせ
                </h5>
                <a href="{{ route('news.index') }}" class="btn btn-sm btn-outline-light">
                    一覧を見る <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($news as $item)
                    <a href="{{ route('news.show', $item) }}" class="d-block px-3 py-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>{{ $item->title }}</strong>
                            <small class="text-muted ms-2 text-nowrap">{{ $item->published_at->format('Y/m/d') }}</small>
                        </div>
                        <div class="text-muted small mt-1">{{ Str::limit($item->body, 100) }}</div>
                    </a>
                @empty
                    <p class="text-muted p-3 mb-0">現在、お知らせはありません。</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event"></i> 今後のイベント
                </h5>
            </div>
            <div class="card-body p-0">
                @forelse($events as $event)
                    <div class="px-3 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>{{ $event->title }}</strong>
                            <small class="text-muted ms-2 text-nowrap">{{ $event->event_date->format('Y/m/d') }}</small>
                        </div>
                        @if($event->location)
                            <div class="text-muted small mt-1"><i class="bi bi-geo-alt"></i> {{ $event->location }}</div>
                        @endif
                        @if($event->deadline)
                            <div class="text-muted small"><i class="bi bi-clock"></i> 申込締切：{{ $event->deadline->format('Y/m/d') }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted p-3 mb-0">現在、予定されているイベントはありません。</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@endsection
