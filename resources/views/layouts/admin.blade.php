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
                display: none; /* オフキャンバスで代替 */
            }
            .main-content {
                margin-left: 0;
                padding: 12px;
            }
            .top-bar {
                padding: 10px 12px;
                margin-bottom: 12px;
                border-radius: 6px;
            }
            .stats-card {
                padding: 12px 16px;
                margin-bottom: 12px;
            }
            .stats-card h3 {
                font-size: 1.5rem;
            }
            .card-header {
                padding: 10px 14px;
            }
            .table th, .table td {
                font-size: 0.8rem;
                padding: 6px 8px;
                white-space: nowrap;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- サイドバー (PC用：固定表示) -->
    <div class="sidebar">
        @include('layouts._admin_nav')
    </div>

    <!-- オフキャンバス (スマホ用) -->
    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="adminOffcanvas" aria-labelledby="adminOffcanvasLabel" style="width: 260px; background-color: var(--primary-color); color: white;">
        <div class="offcanvas-header" style="border-bottom: 1px solid rgba(255,255,255,0.15);">
            <div>
                <h6 class="mb-0 text-white">松.net @if(app()->environment('local'))<span class="badge bg-danger" style="font-size:0.45em;">LOCAL</span>@endif</h6>
                <small class="text-white-50">管理画面</small>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('layouts._admin_nav')
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <!-- トップバー -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                {{-- スマホのみハンバーガー --}}
                <button class="btn btn-sm d-lg-none"
                        style="color: var(--primary-color); border-color: var(--primary-color);"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#adminOffcanvas"
                        aria-controls="adminOffcanvas">
                    <i class="bi bi-list fs-5"></i>
                </button>
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
