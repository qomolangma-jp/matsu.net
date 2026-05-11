@extends('layouts.admin')

@section('title', '設定 - 松.net 管理画面')
@section('page-title', 'システム設定')

@section('content')

<form action="{{ route('admin.settings.update') }}" method="POST">
    @csrf
    @method('PUT')

    {{-- 基本情報 --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-info-circle"></i> 基本情報
        </div>
        <div class="card-body">
            @php
                $s = $settings->get('general', collect());
                $byKey = collect($s)->keyBy('key');
            @endphp

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="site_name" class="form-label required">サイト名</label>
                    <input type="text" class="form-control @error('site_name') is-invalid @enderror"
                           id="site_name" name="site_name"
                           value="{{ old('site_name', $byKey['site_name']['value'] ?? '') }}"
                           maxlength="100">
                    <div class="form-text">{{ $byKey['site_name']['description'] ?? '' }}</div>
                    @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="association_name" class="form-label required">同窓会名</label>
                    <input type="text" class="form-control @error('association_name') is-invalid @enderror"
                           id="association_name" name="association_name"
                           value="{{ old('association_name', $byKey['association_name']['value'] ?? '') }}"
                           maxlength="100">
                    <div class="form-text">{{ $byKey['association_name']['description'] ?? '' }}</div>
                    @error('association_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="admin_email" class="form-label">管理者メールアドレス</label>
                    <input type="email" class="form-control @error('admin_email') is-invalid @enderror"
                           id="admin_email" name="admin_email"
                           value="{{ old('admin_email', $byKey['admin_email']['value'] ?? '') }}"
                           maxlength="255">
                    <div class="form-text">{{ $byKey['admin_email']['description'] ?? '' }}</div>
                    @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- 登録受付 --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-plus"></i> 新規登録受付
        </div>
        <div class="card-body">
            @php
                $sr = $settings->get('registration', collect());
                $byKeyR = collect($sr)->keyBy('key');
                $isOpen = old('registration_open', $byKeyR['registration_open']['value'] ?? '1') === '1';
            @endphp

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="registration_open" name="registration_open"
                           value="1" {{ $isOpen ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="registration_open">
                        新規登録を受け付ける
                    </label>
                </div>
                <div class="form-text">{{ $byKeyR['registration_open']['description'] ?? '' }}</div>
            </div>

            <div class="mb-3">
                <label for="registration_closed_message" class="form-label">受付停止時のメッセージ</label>
                <textarea class="form-control @error('registration_closed_message') is-invalid @enderror"
                          id="registration_closed_message" name="registration_closed_message"
                          rows="3" maxlength="1000">{{ old('registration_closed_message', $byKeyR['registration_closed_message']['value'] ?? '') }}</textarea>
                <div class="form-text">{{ $byKeyR['registration_closed_message']['description'] ?? '' }}</div>
                @error('registration_closed_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- LINE設定 --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-chat-dots"></i> LINE Messaging API
        </div>
        <div class="card-body">
            @php
                $sl = $settings->get('line', collect());
                $byKeyL = collect($sl)->keyBy('key');
            @endphp

            <div class="mb-3" style="max-width: 600px;">
                <label for="line_channel_access_token" class="form-label">Channel Access Token</label>
                <div class="input-group">
                    <input type="password" class="form-control @error('line_channel_access_token') is-invalid @enderror"
                           id="line_channel_access_token" name="line_channel_access_token"
                           value="{{ old('line_channel_access_token', $byKeyL['line_channel_access_token']['value'] ?? '') }}"
                           maxlength="500" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="toggleToken"
                            onclick="toggleTokenVisibility()">
                        <i class="bi bi-eye" id="tokenEyeIcon"></i>
                    </button>
                </div>
                <div class="form-text">{{ $byKeyL['line_channel_access_token']['description'] ?? '' }}</div>
                @error('line_channel_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3" style="max-width: 600px;">
                <label for="liff_id" class="form-label">LIFF ID</label>
                <input type="text" class="form-control @error('liff_id') is-invalid @enderror"
                       id="liff_id" name="liff_id"
                       value="{{ old('liff_id', $byKeyL['liff_id']['value'] ?? '') }}"
                       maxlength="100" placeholder="1234567890-abcdefgh">
                <div class="form-text">{{ $byKeyL['liff_id']['description'] ?? 'LIFF IDを設定すると、LINE通知のリンクがLIFF URL経由（LINE内ブラウザで開く）になります。' }}</div>
                @error('liff_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> 設定を保存
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function toggleTokenVisibility() {
    const input = document.getElementById('line_channel_access_token');
    const icon  = document.getElementById('tokenEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endpush
