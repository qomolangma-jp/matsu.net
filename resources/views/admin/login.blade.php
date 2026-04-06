<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理者ログイン - 松.net</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body style="background-color: #f8f9fa;">

<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow" style="width: 100%; max-width: 420px;">
        <div class="card-header text-center py-4" style="background-color: #2c5f2d;">
            <h4 class="mb-0 text-white fw-bold">
                <i class="bi bi-shield-lock me-2"></i>管理者ログイン
            </h4>
            <small class="text-white-50">松.net 管理システム</small>
        </div>
        <div class="card-body p-4">

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">メールアドレス</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">パスワード</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">ログイン状態を保持する</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg" style="background-color: #2c5f2d; border-color: #2c5f2d;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> ログイン
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center text-muted small py-3">
            マスター管理者・学年管理者専用
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
