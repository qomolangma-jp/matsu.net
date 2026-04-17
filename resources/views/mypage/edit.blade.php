@extends('layouts.app')

@section('title', 'プロフィール編集 - 松.net')

@section('content')
<div class="row">
    @include('mypage._sidebar')

    <!-- メインコンテンツ -->
    <div class="col-12 col-md-9">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>入力エラーがあります：</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> プロフィール編集
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('mypage.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- 姓 -->
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label required">姓</label>
                            <input type="text" 
                                   class="form-control @error('last_name') is-invalid @enderror" 
                                   id="lastName" 
                                   name="last_name" 
                                   value="{{ old('last_name', $user->last_name) }}" 
                                   required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 名 -->
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label required">名</label>
                            <input type="text" 
                                   class="form-control @error('first_name') is-invalid @enderror" 
                                   id="firstName" 
                                   name="first_name" 
                                   value="{{ old('first_name', $user->first_name) }}" 
                                   required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- フリガナ（姓） -->
                        <div class="col-md-6 mb-3">
                            <label for="lastNameKana" class="form-label">フリガナ（姓）</label>
                            <input type="text" 
                                   class="form-control @error('last_name_kana') is-invalid @enderror" 
                                   id="lastNameKana" 
                                   name="last_name_kana" 
                                   value="{{ old('last_name_kana', $user->last_name_kana) }}" 
                                   placeholder="マツネット">
                            @error('last_name_kana')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- フリガナ（名） -->
                        <div class="col-md-6 mb-3">
                            <label for="firstNameKana" class="form-label">フリガナ（名）</label>
                            <input type="text" 
                                   class="form-control @error('first_name_kana') is-invalid @enderror" 
                                   id="firstNameKana" 
                                   name="first_name_kana" 
                                   value="{{ old('first_name_kana', $user->first_name_kana) }}" 
                                   placeholder="タロウ">
                            @error('first_name_kana')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 生年月日 -->
                    <div class="mb-3">
                        <label for="birthDate" class="form-label required">生年月日</label>
                        <input type="date" 
                               class="form-control @error('birth_date') is-invalid @enderror" 
                               id="birthDate" 
                               name="birth_date" 
                               value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}" 
                               required>
                        @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- メールアドレス -->
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}" 
                               placeholder="example@example.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 電話番号 -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">電話番号</label>
                        <input type="tel" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone', $user->phone) }}" 
                               placeholder="090-1234-5678">
                        @error('phone')
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
                                   value="{{ old('postal_code', $user->postal_code) }}" 
                                   placeholder="123-4567">
                            <button class="btn btn-outline-secondary" type="button" id="searchAddressBtn">
                                <i class="bi bi-search"></i> 住所検索
                            </button>
                            @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 住所 -->
                    <div class="mb-3">
                        <label for="address" class="form-label">住所</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" 
                                  name="address" 
                                  rows="3" 
                                  placeholder="東京都千代田区...">{{ old('address', $user->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <!-- 変更不可項目 -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>変更できない項目：</strong> 卒業年度、LINE ID、権限
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('mypage.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> キャンセル
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> 更新する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

@push('scripts')
<script>
// 郵便番号から住所を検索
document.getElementById('searchAddressBtn')?.addEventListener('click', function() {
    const postalCode = document.getElementById('postalCode').value.replace(/[^0-9]/g, '');
    
    if (postalCode.length !== 7) {
        alert('郵便番号は7桁で入力してください');
        return;
    }
    
    fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 200 && data.results) {
                const result = data.results[0];
                const address = result.address1 + result.address2 + result.address3;
                document.getElementById('address').value = address;
            } else {
                alert('住所が見つかりませんでした');
            }
        })
        .catch(error => {
            console.error('住所検索エラー:', error);
            alert('住所検索に失敗しました');
        });
});
</script>
@endpush
@endsection
