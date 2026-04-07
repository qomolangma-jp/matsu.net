@extends('layouts.app')

@section('title', 'お知らせ一覧 - 松.net')

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
                <a href="{{ route('news.index') }}" class="list-group-item list-group-item-action active">
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
                    <a href="{{ route('admin.news.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-newspaper"></i> ニュース管理
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
                    <i class="bi bi-newspaper"></i> お知らせ一覧
                </h5>
            </div>
            <div class="card-body">
                <!-- キーワード検索 -->
                <form method="GET" action="{{ route('news.index') }}" class="mb-4">
                    <div class="input-group">
                        <input
                            type="text"
                            name="keyword"
                            class="form-control"
                            placeholder="キーワードで検索..."
                            value="{{ request('keyword') }}"
                        >
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-search"></i> 検索
                        </button>
                        @if(request('keyword'))
                            <a href="{{ route('news.index') }}" class="btn btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </form>

                @if($news->isEmpty())
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        @if(request('keyword'))
                            「{{ request('keyword') }}」に一致するお知らせはありません。
                        @else
                            現在、お知らせはありません。
                        @endif
                    </p>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($news as $item)
                            <a href="{{ route('news.show', $item) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 me-3">
                                        @if($item->is_top_display)
                                            <span class="badge me-1" style="background-color: #2c5f2d;">重要</span>
                                        @endif
                                        <strong>{{ $item->title }}</strong>
                                        <p class="mb-0 text-muted small mt-1">
                                            {{ Str::limit(strip_tags($item->body), 80) }}
                                        </p>
                                    </div>
                                    <small class="text-muted text-nowrap">
                                        {{ $item->published_at->format('Y/m/d') }}
                                    </small>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $news->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
