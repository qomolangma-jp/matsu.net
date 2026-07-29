<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ローカルログイン切替 - 松高.net</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-xl-11 col-xxl-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill"></i> ローカルログイン切替
                        </h5>
                        <span class="badge bg-light text-primary">local only</span>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <p class="text-muted mb-3">
                            学年管理者・一般ユーザー・マスター管理者をワンクリックで切り替えて、ローカルで配信対象や表示範囲を検証できます。
                        </p>

                        <div class="alert alert-info">
                            <strong>使い方：</strong>
                            <ol class="mb-0">
                                <li>下の一覧から対象ユーザーを選んでログインします。</li>
                                <li>遷移先を「管理ニュース」「管理イベント」などに変えると、確認したい画面へ直接入れます。</li>
                                <li>同時に複数人で確認したい場合は、別ブラウザ・シークレットウィンドウ・別プロファイルを使ってください。</li>
                            </ol>
                        </div>

                        <form method="GET" action="{{ route('test.auto.login') }}" class="row g-2 align-items-end mb-4">
                            <div class="col-md-4">
                                <label for="keyword" class="form-label">検索</label>
                                <input type="text"
                                       class="form-control"
                                       id="keyword"
                                       name="keyword"
                                       value="{{ $keyword }}"
                                       placeholder="氏名・メール・LINE ID">
                            </div>
                            <div class="col-md-3">
                                <label for="role" class="form-label">権限</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">すべて</option>
                                    <option value="master_admin" {{ $role === 'master_admin' ? 'selected' : '' }}>マスター管理者</option>
                                    <option value="year_admin" {{ $role === 'year_admin' ? 'selected' : '' }}>学年管理者</option>
                                    <option value="general" {{ $role === 'general' ? 'selected' : '' }}>一般ユーザー</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="graduation_year" class="form-label">卒業年度</label>
                                <select class="form-select" id="graduation_year" name="graduation_year">
                                    <option value="">すべて</option>
                                    @foreach($graduationYears as $year)
                                        <option value="{{ $year }}" {{ (string) $graduationYear === (string) $year ? 'selected' : '' }}>
                                            {{ $year }}年
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> 絞り込む
                                </button>
                                <a href="{{ route('test.auto.login') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> リセット
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>氏名</th>
                                        <th>卒業年度</th>
                                        <th>権限</th>
                                        <th>承認</th>
                                        <th>メール</th>
                                        <th>LINE ID</th>
                                        <th style="width: 280px;">ログイン</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td>{{ $user->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $user->full_name }}</div>
                                                <div class="text-muted small">{{ $user->full_name_kana }}</div>
                                            </td>
                                            <td>{{ $user->graduation_year ? $user->graduation_year . '年' : '-' }}</td>
                                            <td>
                                                @if($user->role === 'master_admin')
                                                    <span class="badge bg-danger">マスター管理者</span>
                                                @elseif($user->role === 'year_admin')
                                                    <span class="badge bg-primary">学年管理者</span>
                                                @else
                                                    <span class="badge bg-secondary">一般ユーザー</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($user->approval_status === 'approved')
                                                    <span class="badge bg-success">承認済み</span>
                                                @elseif($user->approval_status === 'pending')
                                                    <span class="badge bg-warning text-dark">承認待ち</span>
                                                @else
                                                    <span class="badge bg-dark">却下</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $user->email ?: '-' }}</td>
                                            <td><code>{{ $user->line_id }}</code></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('test.auto.login.submit') }}">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                        <input type="hidden" name="redirect_to" value="{{ in_array($user->role, ['master_admin', 'year_admin']) ? 'admin_users' : 'mypage' }}">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-box-arrow-in-right"></i> 標準
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('test.auto.login.submit') }}">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                        <input type="hidden" name="redirect_to" value="news">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            お知らせ
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('test.auto.login.submit') }}">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                        <input type="hidden" name="redirect_to" value="events">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            イベント
                                                        </button>
                                                    </form>

                                                    @if(in_array($user->role, ['master_admin', 'year_admin']))
                                                        <form method="POST" action="{{ route('test.auto.login.submit') }}">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                            <input type="hidden" name="redirect_to" value="admin_news">
                                                            <button type="submit" class="btn btn-sm btn-outline-dark">管理ニュース</button>
                                                        </form>

                                                        <form method="POST" action="{{ route('test.auto.login.submit') }}">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                            <input type="hidden" name="redirect_to" value="admin_events">
                                                            <button type="submit" class="btn btn-sm btn-outline-dark">管理イベント</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">該当ユーザーがいません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-4">

                        <details>
                            <summary class="fw-semibold">LINE IDを直接指定して試す</summary>
                            <div class="mt-3 row g-2 align-items-end">
                                <div class="col-md-9">
                                    <label for="testLineId" class="form-label">LINE ID</label>
                                    <input type="text"
                                           class="form-control"
                                           id="testLineId"
                                           placeholder="local_test_6995837470805">
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="button" class="btn btn-outline-primary" onclick="checkLoginByLineId()">
                                        <i class="bi bi-lightning-charge"></i> 自動ログイン
                                    </button>
                                </div>
                            </div>
                        </details>
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
        function checkLoginByLineId() {
            const lineId = document.getElementById('testLineId').value;
            if (!lineId) {
                alert('LINE IDを入力してください');
                return;
            }

            window.location.href = `{{ route('register.form') }}?line_id=${encodeURIComponent(lineId)}`;
        }

        document.getElementById('testLineId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkLoginByLineId();
            }
        });
    </script>
</body>
</html>
