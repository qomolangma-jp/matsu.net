<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>テストログイン - 松.net</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c5f2d, #97bc62);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">松.net 管理画面</h3>
                    <p class="text-center text-muted mb-4">テストユーザーでログイン</p>
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="d-grid gap-3">
                        <form method="POST" action="{{ route('test.login') }}">
                            @csrf
                            <input type="hidden" name="email" value="master@matsu.localhost">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                マスター管理者でログイン
                                <br><small style="font-size: 0.8rem;">master@matsu.localhost（全学年アクセス可）</small>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('test.login') }}">
                            @csrf
                            <input type="hidden" name="email" value="admin2018@matsu.localhost">
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                学年管理者（2018年）でログイン
                                <br><small style="font-size: 0.8rem;">admin2018@matsu.localhost（2018年卒のみ）</small>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('test.login') }}">
                            @csrf
                            <input type="hidden" name="email" value="admin2019@matsu.localhost">
                            <button type="submit" class="btn btn-info btn-lg w-100">
                                学年管理者（2019年）でログイン
                                <br><small style="font-size: 0.8rem;">admin2019@matsu.localhost（2019年卒のみ）</small>
                            </button>
                        </form>
                    </div>

                    <div class="text-center mt-4">
                        <small class="text-muted">※ 開発環境専用のテストログイン機能です</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
