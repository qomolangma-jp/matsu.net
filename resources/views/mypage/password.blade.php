@extends('layouts.app')

@section('title', 'パスワード変更 - 松.net')

@section('content')
<div class="row">
    @include('mypage._sidebar')

    <!-- メインコンテンツ -->
    <div class="col-12 col-md-9">
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
