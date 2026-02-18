<!-- ローカル環境専用：既存ユーザー自動ログインテスト -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>自動ログインテスト - 松.net</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning-fill"></i> 自動ログインテスト
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            既存のLINE IDを入力して、自動ログイン機能をテストします。
                        </p>

                        <div class="alert alert-info">
                            <strong>テスト手順：</strong>
                            <ol class="mb-0">
                                <li>既存のLINE IDを入力（例: local_test_6995837470805）</li>
                                <li>「チェック」ボタンをクリック、またはEnterキーを押す</li>
                                <li>既存ユーザーの場合、自動ログインされます</li>
                            </ol>
                        </div>

                        <form id="testForm">
                            <div class="mb-3">
                                <label for="testLineId" class="form-label">LINE ID</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="testLineId" 
                                       placeholder="local_test_6995837470805"
                                       value="local_test_6995837470805">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" onclick="checkLogin()">
                                    <i class="bi bi-box-arrow-in-right"></i> チェック
                                </button>
                                <a href="{{ route('register.form') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> 登録画面に戻る
                                </a>
                            </div>
                        </form>

                        <hr class="my-4">

                        <h6>既存ユーザー一覧（参考）</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>LINE ID</th>
                                        <th>氏名</th>
                                        <th>権限</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $users = \App\Models\User::orderBy('id')->limit(5)->get();
                                    @endphp
                                    @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <code>{{ $user->line_id }}</code>
                                        </td>
                                        <td>{{ $user->full_name }}</td>
                                        <td>
                                            @if($user->role === 'master_admin')
                                                <span class="badge bg-danger">マスター</span>
                                            @elseif($user->role === 'year_admin')
                                                <span class="badge bg-warning">学年管理者</span>
                                            @else
                                                <span class="badge bg-secondary">一般</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-check"></i> この機能はローカル環境のみで動作します
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkLogin() {
            const lineId = document.getElementById('testLineId').value;
            if (!lineId) {
                alert('LINE IDを入力してください');
                return;
            }
            
            // 登録画面にLINE IDパラメータ付きでリダイレクト
            window.location.href = `{{ route('register.form') }}?line_id=${encodeURIComponent(lineId)}`;
        }

        // Enterキーでも実行
        document.getElementById('testLineId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkLogin();
            }
        });
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
