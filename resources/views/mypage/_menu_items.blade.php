<div class="list-group list-group-flush">
    <a href="{{ route('mypage.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('mypage.index') ? 'active' : '' }}">
        <i class="bi bi-house-door"></i> マイページ
    </a>
    <a href="{{ route('mypage.edit') }}" class="list-group-item list-group-item-action {{ request()->routeIs('mypage.edit') ? 'active' : '' }}">
        <i class="bi bi-pencil-square"></i> プロフィール編集
    </a>
    <a href="{{ route('mypage.password') }}" class="list-group-item list-group-item-action {{ request()->routeIs('mypage.password') ? 'active' : '' }}">
        <i class="bi bi-key"></i> パスワード変更
    </a>

    @if(Auth::user()->approval_status === 'approved')
    <a href="{{ route('news.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('news.*') ? 'active' : '' }}">
        <i class="bi bi-newspaper"></i> お知らせ一覧
    </a>
    <a href="{{ route('events.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('events.*') ? 'active' : '' }}">
        <i class="bi bi-calendar-event"></i> イベント一覧
    </a>
    @endif

    @if(Auth::check() && in_array(Auth::user()->role, ['master_admin', 'year_admin']))
        <div class="list-group-item bg-light">
            <small class="text-muted fw-bold">管理メニュー</small>
        </div>
        <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-people"></i> 名簿管理
        </a>
        @if(Auth::user()->role === 'master_admin')
            <a href="{{ route('admin.reference_rosters.index') }}" class="list-group-item list-group-item-action">
                <i class="bi bi-database"></i> 参照名簿
            </a>
            <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action">
                <i class="bi bi-tags"></i> カテゴリー管理
            </a>
        @endif
        <a href="{{ route('admin.news.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-newspaper"></i> ニュース管理
        </a>
        <a href="{{ route('admin.events.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-calendar-event"></i> イベント管理
        </a>
    @endif

    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
       class="list-group-item list-group-item-action text-danger">
        <i class="bi bi-box-arrow-right"></i> ログアウト
    </a>
</div>
