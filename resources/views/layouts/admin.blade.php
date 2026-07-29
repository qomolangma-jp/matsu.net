<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理画面 - 松高.net')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
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

        .top-bar-main {
            min-width: 0;
            flex: 1 1 auto;
        }

        .top-bar-title-group {
            min-width: 0;
            flex-wrap: nowrap;
        }

        .top-bar-inline-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: none;
            min-width: 0;
            padding-bottom: 2px;
        }

        .top-bar-inline-stats::-webkit-scrollbar {
            display: none;
        }

        .top-bar-inline-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f6f8f9;
            border: 1px solid #e7ecef;
            white-space: nowrap;
            line-height: 1;
        }

        .top-bar-inline-stat-label {
            font-size: 0.72rem;
            color: #5f6b76;
        }

        .top-bar-inline-stat-value {
            font-size: 0.88rem;
            font-weight: 700;
            color: #212529;
        }

        .top-bar-inline-stat.is-approved {
            background: #eef9f2;
            border-color: #cae9d5;
        }

        .top-bar-inline-stat.is-pending {
            background: #fff7e8;
            border-color: #f6ddb1;
        }

        .top-bar-inline-stat.is-unreachable {
            background: #fff0f1;
            border-color: #f4c9ce;
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
            height: 58px;
            padding: 6px 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
        }

        .stats-card h3 {
            font-size: 1.25rem;
            line-height: 1;
            margin: 0;
        }

        .stats-card small {
            opacity: 0.9;
            display: block;
            font-size: 0.72rem;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .form-label.required::after {
            content: " *";
            color: #dc3545;
        }

        ::placeholder {
            color: #bcc3cb !important;
            opacity: 1;
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
            .top-bar-inline-stats {
                display: none;
            }
            .stats-card {
                height: 56px;
                padding: 6px 10px;
                margin-bottom: 8px;
                gap: 1px;
            }
            .stats-card h3 {
                font-size: 1.1rem;
            }
            .stats-card small {
                font-size: 0.68rem;
            }

            /* 統計カードを1ブロックのリスト表示に切り替え */
            .stats-summary {
                background: #ffffff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                margin-left: 0;
                margin-right: 0;
                overflow: hidden;
            }

            .stats-summary > [class*="col-"] {
                width: 100%;
                padding-left: 0;
                padding-right: 0;
                margin-bottom: 0;
            }

            .stats-summary > [class*="col-"]:not(:last-child) {
                border-bottom: 1px solid #f1f3f5;
            }

            .stats-summary .stats-card {
                height: auto;
                min-height: 0;
                margin-bottom: 0;
                padding: 10px 12px;
                border-radius: 0;
                background: transparent !important;
                color: #212529;
                box-shadow: none;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .stats-summary .stats-card small {
                font-size: 0.78rem;
                line-height: 1.2;
                opacity: 0.9;
                color: #495057;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .stats-summary .stats-card h3 {
                font-size: 1rem;
                line-height: 1;
                margin: 0;
                font-weight: 700;
                color: #212529;
                text-align: right;
                white-space: nowrap;
                flex-shrink: 0;
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
                <div class="d-flex align-items-center gap-2 mb-1">
                    <img src="{{ asset('images/logo_banner.png') }}" alt="松高.net" style="height: 50px; width: auto;">
                    @if(app()->environment('local'))<span class="badge bg-danger" style="font-size:0.45em;">LOCAL</span>@endif
                </div>
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
        <div class="top-bar d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 top-bar-main">
                {{-- スマホのみハンバーガー --}}
                <button class="btn btn-sm d-lg-none"
                        style="color: var(--primary-color); border-color: var(--primary-color);"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#adminOffcanvas"
                        aria-controls="adminOffcanvas">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-flex align-items-center gap-3 top-bar-title-group">
                    <h5 class="mb-0 flex-shrink-0">@yield('page-title', 'ページタイトル')</h5>
                    @yield('top-summary')
                </div>
            </div>
            <div class="flex-shrink-0">
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
