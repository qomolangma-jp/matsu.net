<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理画面 - 松.net')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: {{ app()->environment('local') ? '#1a4a7a' : '#2c5f2d' }};
            --secondary-color: {{ app()->environment('local') ? '#5b9bd5' : '#97bc62' }};
            --sidebar-width: 250px;
        }
        
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
            overflow-y: auto;
        }

        .sidebar .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--secondary-color);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .top-bar {
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 8px;
        }

        .card-header {
            background-color: white;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            padding: 15px 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .badge {
            padding: 0.35em 0.65em;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stats-card h3 {
            font-size: 2rem;
            margin: 0;
        }

        .stats-card small {
            opacity: 0.9;
        }

        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- サイドバー -->
    <div class="sidebar">
        <div class="logo">
            <h4 class="mb-1">松.net @if(app()->environment('local'))<span class="badge bg-danger" style="font-size:0.45em; vertical-align:middle;">LOCAL</span>@endif</h4>
            <small>管理画面</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="#">
                <i class="bi bi-speedometer2"></i> ダッシュボード
            </a>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                <i class="bi bi-people"></i> 名簿管理
            </a>
            @if(Auth::check() && Auth::user()->role === 'master_admin')
                <a class="nav-link {{ request()->routeIs('admin.reference_rosters.*') ? 'active' : '' }}" href="{{ route('admin.reference_rosters.index') }}">
                    <i class="bi bi-database"></i> 参照名簿
                </a>
            @endif
            <a class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events.index') }}">
                <i class="bi bi-calendar-event"></i> イベント管理
            </a>
            <a class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">
                <i class="bi bi-newspaper"></i> ニュース管理
            </a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                <i class="bi bi-gear"></i> 設定
            </a>
        </nav>

        <div class="mt-auto p-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <div class="small">{{ Auth::user()?->full_name }}</div>
                    <div class="small opacity-75">
                        @if(Auth::user()?->role === 'master_admin')
                            マスター管理者
                        @elseif(Auth::user()?->role === 'year_admin')
                            学年管理者 ({{ Auth::user()?->graduation_year }}年)
                        @else
                            一般ユーザー
                        @endif
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                    <i class="bi bi-box-arrow-left"></i> ログアウト
                </button>
            </form>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <!-- トップバー -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">@yield('page-title', 'ページタイトル')</h5>
            </div>
            <div>
                @yield('top-actions')
            </div>
        </div>

        <!-- フラッシュメッセージ -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- メインコンテンツエリア -->
        @yield('content')
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
