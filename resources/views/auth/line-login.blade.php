<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LINEログイン - 松高.net</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<main class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <section class="card shadow text-center" style="width: 100%; max-width: 420px;">
        <header class="card-header py-4" style="background-color: #2c5f2d;">
            <img src="{{ asset('images/logo_banner.png') }}" alt="松高.net" style="height: 80px; width: auto;">
            <h1 class="h4 text-white fw-bold mt-2 mb-0">ログイン</h1>
        </header>
        <div class="card-body p-4">
            @if(session('error'))
                <div class="alert alert-danger text-start" role="alert">{{ session('error') }}</div>
            @endif

            <p class="mb-4">LINEアカウントでログインしてください。</p>
            <a href="{{ route('line.login.redirect', ['redirect' => request('redirect', '/mypage')]) }}"
               class="btn btn-lg w-100 text-white"
               style="background-color: #06c755;">
                <i class="bi bi-line me-2"></i>LINEでログイン
            </a>
            <p class="small text-muted mt-3 mb-0">
                LINEに登録したメールアドレスやパスワードは、このサイトには送信されません。
            </p>

            @if(app()->environment('local'))
                <hr>
                <a href="{{ route('test.auto.login') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-people me-1"></i>ローカル検証用ユーザー切替
                </a>
            @endif
        </div>
        <footer class="card-footer text-muted small py-3">
            LINEアカウントが未登録の場合は、新規登録画面へ進みます。
        </footer>
    </section>
</main>
</body>
</html>
