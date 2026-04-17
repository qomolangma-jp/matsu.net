<div class="logo d-none d-lg-block">
    <h4 class="mb-1">松.net @if(app()->environment('local'))<span class="badge bg-danger" style="font-size:0.45em; vertical-align:middle;">LOCAL</span>@endif</h4>
    <small>管理画面</small>
</div>

<nav class="nav flex-column">
    <a class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="#">
        <i class="bi bi-speedometer2"></i> ダッシュボード
    </a>
    <a class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
        <i class="bi bi-people"></i> 名簿管理
    </a>
    @if(Auth::check() && Auth::user()->role === 'master_admin')
        <a class="nav-link text-white {{ request()->routeIs('admin.reference_rosters.*') ? 'active' : '' }}" href="{{ route('admin.reference_rosters.index') }}">
            <i class="bi bi-database"></i> 参照名簿
        </a>
    @endif
    <a class="nav-link text-white {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events.index') }}">
        <i class="bi bi-calendar-event"></i> イベント管理
    </a>
    <a class="nav-link text-white {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">
        <i class="bi bi-newspaper"></i> ニュース管理
    </a>
    <a class="nav-link text-white {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
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
