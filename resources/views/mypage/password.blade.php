@extends('layouts.app')

@section('title', 'パスワード変更 - 松.net')

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
                <a href="{{ route('mypage.password') }}" class="list-group-item list-group-item-action active">
                    <i class="bi bi-key"></i> パスワード変更
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
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-key"></i> パスワード変更
                </h5>
            </div>
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('mypage.password.update') }}" method="POST" style="max-width: 480px;">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                               id="current_password" name="current_password" autocomplete="current-password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" autocomplete="new-password">
                        <div class="form-text">8文字以上で入力してください。</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> パスワードを変更する
                    </button>
                    <a href="{{ route('mypage.index') }}" class="btn btn-outline-secondary ms-2">キャンセル</a>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
