<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '松.net - 同窓生向けWebシステム')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: {{ app()->environment('local') ? '#1a4a7a' : '#2c5f2d' }};
            --secondary-color: {{ app()->environment('local') ? '#5b9bd5' : '#97bc62' }};
        }

        @if(app()->environment('local'))
        .local-badge {
            position: fixed;
            bottom: 16px;
            right: 16px;
            background: #dc3545;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            z-index: 9999;
            letter-spacing: 1px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        @endif
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }

        .list-item-hover {
            transition: background-color 0.15s ease;
            cursor: pointer;
        }
        .list-item-hover:hover,
        .list-item-hover:active {
            background-color: #f0f7f0 !important;
        }

        ::placeholder {
            color: #bcc3cb !important;
            opacity: 1;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">
                <h4 class="mb-0">松.net @if(app()->environment('local'))<small class="badge bg-danger ms-2" style="font-size:0.5em; vertical-align:middle;">LOCAL</small>@endif</h4>
            </a>
            @auth
            <button class="navbar-toggler d-md-none border-0"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#appOffcanvas"
                    aria-controls="appOffcanvas"
                    aria-label="メニューを開く">
                <span class="navbar-toggler-icon"></span>
            </button>
            @endauth
        </div>
    </nav>

    @auth
    {{-- オフキャンバスメニュー (スマホ用) --}}
    <div class="offcanvas offcanvas-start" tabindex="-1" id="appOffcanvas" aria-labelledby="appOffcanvasLabel">
        <div class="offcanvas-header" style="background-color: var(--primary-color); color: white;">
            <h6 class="offcanvas-title mb-0" id="appOffcanvasLabel">
                <i class="bi bi-person-circle me-1"></i> メニュー
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('mypage._menu_items')
        </div>
    </div>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    @endauth

    @if(app()->environment('local'))
    <div class="local-badge">⚠ LOCAL</div>
    @endif

    <!-- メインコンテンツ -->
    <main class="container pb-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- LIFF SDK -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    
    @stack('scripts')
</body>
</html>
