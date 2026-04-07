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
        </div>
    </nav>

    @if(app()->environment('local'))
    <div class="local-badge">⚠ LOCAL</div>
    @endif

    <!-- メインコンテンツ -->
    <main class="container pb-5">
        @yield('content')
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- LIFF SDK -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    
    @stack('scripts')
</body>
</html>
