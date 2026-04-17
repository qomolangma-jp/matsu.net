{{-- PCサイドバー (md以上のみ表示) --}}
<div class="col-md-3 mb-4 d-none d-md-block">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-person-circle"></i> メニュー
            </h6>
        </div>
        @include('mypage._menu_items')
    </div>
</div>

{{-- ログアウトフォーム --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
