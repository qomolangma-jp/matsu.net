@extends('layouts.admin')

@section('title', 'ユーザー編集 - 松.net')
@section('page-title', 'ユーザー編集')

@section('top-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 一覧に戻る
    </a>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

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

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <!-- 基本情報 -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person"></i> 基本情報
                </div>
                <div class="card-body">
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

                    <hr class="my-4">

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
                </div>
            </div>
        </div>

        <!-- 管理項目 -->
        <div class="col-md-4">
            <!-- 卒業年度（表示のみ） -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-mortarboard"></i> 卒業年度
                </div>
                <div class="card-body">
                    <div class="alert alert-secondary mb-0">
                        <h4 class="mb-1">{{ $user->graduation_year }}年</h4>
                        <p class="mb-0 text-muted">高校{{ $user->graduation_year - 1947 }}回期</p>
                    </div>
                    <small class="text-muted">※卒業年度は変更できません</small>
                </div>
            </div>

            <!-- カテゴリー・権限 -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-tags"></i> カテゴリー・権限
                </div>
                <div class="card-body">
                    <!-- カテゴリー -->
                    <div class="mb-3">
                        <label class="form-label">カテゴリー</label>
                        <div class="row">
                            @forelse($categories as $category)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="categories[]" 
                                               value="{{ $category->id }}" 
                                               id="category{{ $category->id }}"
                                               {{ in_array($category->id, old('categories', $user->categories->pluck('id')->toArray())) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="category{{ $category->id }}">
                                            {{ $category->name }}
                                            @if($category->type === 'district')
                                                <span class="badge bg-primary bg-opacity-50 ms-1">地区会</span>
                                            @elseif($category->type === 'role')
                                                <span class="badge bg-info bg-opacity-50 ms-1">役職</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted">登録されているカテゴリーがありません。</p>
                                </div>
                            @endforelse
                        </div>
                        <small class="text-muted">複数選択可能です。カテゴリーの追加は<a href="{{ route('admin.categories.index') }}" target="_blank">カテゴリー管理</a>から行えます。</small>
                    </div>

                    <!-- 権限 -->
                    <div class="mb-3">
                        <label for="role" class="form-label required">権限</label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" 
                                name="role" 
                                required
                                @if(Auth::user()->role !== 'master_admin') disabled @endif>
                            <option value="general" {{ old('role', $user->role) === 'general' ? 'selected' : '' }}>一般ユーザー</option>
                            <option value="year_admin" {{ old('role', $user->role) === 'year_admin' ? 'selected' : '' }}>学年管理者</option>
                            <option value="master_admin" {{ old('role', $user->role) === 'master_admin' ? 'selected' : '' }}>マスター管理者</option>
                        </select>
                        @if(Auth::user()->role !== 'master_admin')
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <small class="text-muted">※権限の変更はマスター管理者のみ可能です</small>
                        @endif
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 郵送物不達フラグ -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" 
                               type="checkbox" 
                               role="switch" 
                               id="mailUnreachable" 
                               name="mail_unreachable" 
                               value="1"
                               {{ old('mail_unreachable', $user->mail_unreachable) ? 'checked' : '' }}>
                        <label class="form-check-label" for="mailUnreachable">
                            郵送物不達
                        </label>
                    </div>
                </div>
            </div>

            <!-- 承認ステータス -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-check-circle"></i> 承認ステータス
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="approvalStatus" class="form-label required">ステータス</label>
                        <select class="form-select @error('approval_status') is-invalid @enderror" 
                                id="approvalStatus" 
                                name="approval_status" 
                                required>
                            <option value="pending" {{ old('approval_status', $user->approval_status) === 'pending' ? 'selected' : '' }}>承認待ち</option>
                            <option value="approved" {{ old('approval_status', $user->approval_status) === 'approved' ? 'selected' : '' }}>承認済み</option>
                            <option value="rejected" {{ old('approval_status', $user->approval_status) === 'rejected' ? 'selected' : '' }}>却下</option>
                        </select>
                        @error('approval_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($user->approved_at)
                        <div class="alert alert-info mb-0">
                            <small>
                                <strong>承認日時：</strong><br>
                                {{ $user->approved_at->format('Y年m月d日 H:i') }}<br>
                                @if($user->approver)
                                    <strong>承認者：</strong>{{ $user->approver->full_name }}
                                @endif
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- LINE情報（表示のみ） -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-line"></i> LINE情報
                </div>
                <div class="card-body">
                    <small class="text-muted">LINE ID:</small>
                    <p class="mb-1 text-break">{{ $user->line_id }}</p>
                    <small class="text-muted">登録日時:</small>
                    <p class="mb-0">{{ $user->created_at->format('Y年m月d日 H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ボタン -->
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> キャンセル
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> 更新する
        </button>
    </div>
</form>
@endsection

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
