@extends('layouts.app')

@section('title', 'パスワード再発行 - 松高.net')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-envelope-lock me-1"></i> パスワード再発行</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    登録済みの「メールアドレス」と「生年月日」が一致した場合、
                    新しいパスワードを自動生成してメールで送信します。
                </p>

                @if($errors->has('error'))
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('error') }}
                    </div>
                @endif

                <form action="{{ route('password.reissue.send') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               placeholder="example@example.com"
                               autocomplete="email"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="birth_date" class="form-label">生年月日 <span class="text-danger">*</span></label>
                        <input type="date"
                               id="birth_date"
                               name="birth_date"
                               class="form-control @error('birth_date') is-invalid @enderror"
                               value="{{ old('birth_date') }}"
                               max="{{ now()->subDay()->format('Y-m-d') }}"
                               required>
                        @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> 再発行する
                        </button>
                        <a href="{{ route('admin.login.form') }}" class="btn btn-outline-secondary">ログイン画面へ戻る</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
