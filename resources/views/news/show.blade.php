@extends('layouts.app')

@section('title', $news->title . ' - 松.net')

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
        <div class="mb-3">
            <a href="{{ route('news.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> お知らせ一覧に戻る
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="mb-0">
                        @if($news->is_top_display)
                            <span class="badge me-1" style="background-color: #97bc62;">重要</span>
                        @endif
                        {{ $news->title }}
                    </h5>
                    <small class="text-white-50 text-nowrap ms-3">
                        {{ $news->published_at->format('Y年m月d日') }}
                    </small>
                </div>
            </div>
            <div class="card-body">
                @if($news->target_graduation_years && count($news->target_graduation_years) > 0)
                    <div class="mb-3">
                        <span class="badge bg-secondary">
                            <i class="bi bi-mortarboard"></i>
                            対象：{{ implode('・', array_map(fn($y) => $y.'年卒', $news->target_graduation_years)) }}
                        </span>
                    </div>
                @endif

                <div class="news-body" style="line-height: 1.8; white-space: pre-wrap;">{{ $news->body }}</div>

                @if($news->creator)
                    <hr>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-person"></i> 投稿者：{{ $news->creator->full_name }}
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('news.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> お知らせ一覧に戻る
            </a>
        </div>
    </div>
</div>
@endsection
