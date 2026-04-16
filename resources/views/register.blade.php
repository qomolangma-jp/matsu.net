@extends('layouts.app')

@section('title', '新規登録 - 松.net')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">新規登録</h5>
            </div>
            <div class="card-body p-4">
                <!-- エラーメッセージ表示 -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register.submit') }}" method="POST" id="registerForm">
                    @csrf
                    
                    <!-- LINE ID -->
                    @if(config('app.env') === 'local')
                        <!-- ローカル環境：手動入力 -->
                        <div class="mb-3">
                            <label for="lineId" class="form-label required">LINE ID（テスト用）</label>
                            <input type="text" 
                                   class="form-control @error('line_id') is-invalid @enderror" 
                                   id="lineId" 
                                   name="line_id" 
                                   value="{{ old('line_id', request('line_id', 'local_test_' . uniqid())) }}" 
                                   required
                                   placeholder="例: local_test_12345">
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> ローカル環境では仮のLINE IDを使用します
                                <br><i class="bi bi-lightning-fill text-warning"></i> 既存のLINE IDを入力すると自動ログインします
                            </div>
                            @error('line_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <!-- 本番環境：LIFF経由で自動取得（/auth/line から引き継ぎも可） -->
                        <input type="hidden" name="line_id" id="lineId" value="{{ $lineId ?? '' }}">
                    @endif

                    <!-- LIFF デバッグ表示（暫定的に非表示） -->
                    <div id="liff-status" class="mb-3 p-2 rounded border" style="display:none;font-size:0.85em;font-family:monospace;background:#f8f9fa;border-color:#dee2e6!important;min-height:2.5em;">⏳ 初期化中...</div>

                    <!-- 氏名（姓） -->
                    <div class="mb-3">
                        <label for="lastName" class="form-label required">姓</label>
                        <input type="text" 
                               class="form-control @error('last_name') is-invalid @enderror" 
                               id="lastName" 
                               name="last_name" 
                               value="{{ old('last_name') }}" 
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 氏名（名） -->
                    <div class="mb-3">
                        <label for="firstName" class="form-label required">名</label>
                        <input type="text" 
                               class="form-control @error('first_name') is-invalid @enderror" 
                               id="firstName" 
                               name="first_name" 
                               value="{{ old('first_name') }}" 
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- フリガナ（姓） -->
                    <div class="mb-3">
                        <label for="lastNameKana" class="form-label">フリガナ（姓）</label>
                        <input type="text" 
                               class="form-control @error('last_name_kana') is-invalid @enderror" 
                               id="lastNameKana" 
                               name="last_name_kana" 
                               value="{{ old('last_name_kana') }}" 
                               placeholder="例：マツネット">
                        @error('last_name_kana')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- フリガナ（名） -->
                    <div class="mb-3">
                        <label for="firstNameKana" class="form-label">フリガナ（名）</label>
                        <input type="text" 
                               class="form-control @error('first_name_kana') is-invalid @enderror" 
                               id="firstNameKana" 
                               name="first_name_kana" 
                               value="{{ old('first_name_kana') }}" 
                               placeholder="例：タロウ">
                        @error('first_name_kana')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 生年月日 -->
                    <div class="mb-3">
                        <label class="form-label required">生年月日</label>
                        @php
                            $currentYear   = (int) date('Y');
                            $minYear       = $currentYear - 99;  // 99歳
                            $maxYear       = $currentYear - 15;  // 15歳
                            $oldBirth      = old('birth_date', '');
                            $oldYear       = $oldBirth ? (int) substr($oldBirth, 0, 4) : '';
                            $oldMonth      = $oldBirth ? (int) substr($oldBirth, 5, 2) : '';
                            $oldDay        = $oldBirth ? (int) substr($oldBirth, 8, 2) : '';
                        @endphp
                        {{-- hidden: YYYY-MM-DD に結合してサブミット --}}
                        <input type="hidden" name="birth_date" id="birthDate" value="{{ $oldBirth }}">
                        <div class="d-flex gap-2 align-items-center">
                            {{-- 年 --}}
                            <select id="birthYear" class="form-select @error('birth_date') is-invalid @enderror" style="flex:2;">
                                <option value="">年</option>
                                @for($y = $maxYear; $y >= $minYear; $y--)
                                    <option value="{{ $y }}" {{ $oldYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                                @endfor
                            </select>
                            {{-- 月 --}}
                            <select id="birthMonth" class="form-select @error('birth_date') is-invalid @enderror" style="flex:1;">
                                <option value="">月</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $oldMonth == $m ? 'selected' : '' }}>{{ $m }}月</option>
                                @endfor
                            </select>
                            {{-- 日 --}}
                            <select id="birthDay" class="form-select @error('birth_date') is-invalid @enderror" style="flex:1;">
                                <option value="">日</option>
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ $oldDay == $d ? 'selected' : '' }}>{{ $d }}日</option>
                                @endfor
                            </select>
                        </div>
                        @error('birth_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 卒業年度 -->
                    <div class="mb-3">
                        <label for="graduationTerm" class="form-label required">卒業年度</label>
                        <select class="form-select @error('graduation_term') is-invalid @enderror" 
                                id="graduationTerm" 
                                name="graduation_term" 
                                required>
                            <option value="">生年月日を入力すると選択できます</option>
                        </select>
                        @error('graduation_term')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            生年月日から自動的に候補年度（前後3年分）が表示されます
                        </div>
                    </div>

                    <!-- メールアドレス -->
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               placeholder="example@mail.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 郵便番号 -->
                    <div class="mb-3">
                        <label for="postalCode" class="form-label">郵便番号</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control @error('postal_code') is-invalid @enderror" 
                                   id="postalCode" 
                                   name="postal_code" 
                                   value="{{ old('postal_code') }}" 
                                   placeholder="1234567"
                                   maxlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="searchAddressBtn">
                                住所検索
                            </button>
                            @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text">
                            ハイフンなしで入力してください（例：1234567）
                        </div>
                    </div>

                    <!-- 住所 -->
                    <div class="mb-4">
                        <label for="address" class="form-label">住所</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" 
                                  name="address" 
                                  rows="3" 
                                  placeholder="東京都千代田区...">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 送信ボタン -->
                    <div class="d-grid">
                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
                            登録する
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 補足説明 -->
        <div class="text-center mt-3">
            <small class="text-muted">
                <span class="text-danger">*</span> は必須項目です
            </small>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.LIFF_ID = "{{ config('services.line.liff_id') }}";
</script>
<script src="{{ asset('js/register.js') }}?v={{ time() }}"></script>
<script>
(function () {
    const yearSel  = document.getElementById('birthYear');
    const monthSel = document.getElementById('birthMonth');
    const daySel   = document.getElementById('birthDay');
    const hidden   = document.getElementById('birthDate');

    function pad(n) { return String(n).padStart(2, '0'); }

    function updateDays() {
        const y = parseInt(yearSel.value);
        const m = parseInt(monthSel.value);
        const current = parseInt(daySel.value);
        const max = (y && m) ? new Date(y, m, 0).getDate() : 31;
        // 日の選択肢を再構築
        daySel.innerHTML = '<option value="">日</option>';
        for (let d = 1; d <= max; d++) {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d + '日';
            if (d === current) opt.selected = true;
            daySel.appendChild(opt);
        }
    }

    function syncHidden() {
        const y = yearSel.value;
        const m = monthSel.value;
        const d = daySel.value;
        hidden.value = (y && m && d)
            ? y + '-' + pad(m) + '-' + pad(d)
            : '';
        // register.js の updateGraduationYearOptions を呼ぶ
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    yearSel.addEventListener('change', function () { updateDays(); syncHidden(); });
    monthSel.addEventListener('change', function () { updateDays(); syncHidden(); });
    daySel.addEventListener('change', syncHidden);

    // ページロード時に日の選択肢を初期化
    updateDays();
    // フォーム送信前に hidden が空なら阻止
    document.getElementById('registerForm').addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            yearSel.classList.add('is-invalid');
            monthSel.classList.add('is-invalid');
            daySel.classList.add('is-invalid');
            yearSel.closest('.mb-3').insertAdjacentHTML('beforeend',
                '<div class="text-danger small mt-1" id="birthErr">生年月日を選択してください。</div>');
        }
    });
    [yearSel, monthSel, daySel].forEach(s => s.addEventListener('change', function () {
        this.classList.remove('is-invalid');
        const err = document.getElementById('birthErr');
        if (err) err.remove();
    }));
})();
</script>
@endpush
