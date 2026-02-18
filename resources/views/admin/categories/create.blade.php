@extends('layouts.admin')

@section('title', 'カテゴリー新規作成 - 松.net')
@section('page-title', 'カテゴリー新規作成')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-plus-circle"></i> 新規カテゴリー作成
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf

                    <!-- カテゴリー名 -->
                    <div class="mb-3">
                        <label for="name" class="form-label required">カテゴリー名</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               required
                               maxlength="255"
                               placeholder="例: 中央地区会">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            ユーザーに表示されるカテゴリー名です。
                        </small>
                    </div>

                    <!-- Slug -->
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug（スラッグ）</label>
                        <input type="text" 
                               class="form-control @error('slug') is-invalid @enderror" 
                               id="slug" 
                               name="slug" 
                               value="{{ old('slug') }}"
                               maxlength="255"
                               pattern="[a-z0-9\-_]+"
                               placeholder="例: chuo-district">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            空欄の場合、カテゴリー名から自動生成されます。半角英数字、ハイフン、アンダースコアのみ使用可能です。
                        </small>
                    </div>

                    <!-- 説明 -->
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="カテゴリーの説明や用途を入力">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            管理用のメモです。任意項目です。
                        </small>
                    </div>

                    <!-- タイプ -->
                    <div class="mb-3">
                        <label for="type" class="form-label required">タイプ</label>
                        <select class="form-select @error('type') is-invalid @enderror" 
                                id="type" 
                                name="type" 
                                required>
                            <option value="">選択してください</option>
                            <option value="district" {{ old('type') === 'district' ? 'selected' : '' }}>地区会</option>
                            <option value="role" {{ old('type') === 'role' ? 'selected' : '' }}>役職</option>
                            <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>その他</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            カテゴリーの分類を選択してください。
                        </small>
                    </div>

                    <!-- 表示順 -->
                    <div class="mb-3">
                        <label for="display_order" class="form-label">表示順</label>
                        <input type="number" 
                               class="form-control @error('display_order') is-invalid @enderror" 
                               id="display_order" 
                               name="display_order" 
                               value="{{ old('display_order', 0) }}"
                               min="0"
                               max="999"
                               step="1">
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            数値が小さいほど先頭に表示されます。デフォルトは0です。
                        </small>
                    </div>

                    <!-- アクティブ状態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>アクティブ</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            非アクティブにすると、このカテゴリーは選択肢に表示されなくなります。
                        </small>
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> 戻る
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> 作成
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ヘルプカード -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-question-circle"></i> カテゴリーについて
            </div>
            <div class="card-body">
                <h6>カテゴリーの用途</h6>
                <ul>
                    <li><strong>地区会:</strong> 中央地区会、北地区会などの地区分類</li>
                    <li><strong>役職:</strong> 理事、監事、委員などの役割分類</li>
                    <li><strong>その他:</strong> 特別なグループや属性の分類</li>
                </ul>

                <h6 class="mt-3">カテゴリーの特徴</h6>
                <ul class="mb-0">
                    <li>各ユーザーは複数のカテゴリーに所属できます</li>
                    <li>カテゴリーを使って絞り込み検索が可能です</li>
                    <li>表示順を設定して、選択肢の並び順をカスタマイズできます</li>
                    <li>非アクティブにしても、既に紐づいているユーザーからは解除されません</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
