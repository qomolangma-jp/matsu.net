@extends('layouts.admin')

@section('title', 'カテゴリー編集 - 松.net')
@section('page-title', 'カテゴリー編集')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pencil-square"></i> カテゴリー編集: {{ $category->name }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                    @csrf
                    @method('PUT')

                    <!-- カテゴリー名 -->
                    <div class="mb-3">
                        <label for="name" class="form-label required">カテゴリー名</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $category->name) }}"
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
                               value="{{ old('slug', $category->slug) }}"
                               maxlength="255"
                               pattern="[a-z0-9\-_]+"
                               placeholder="例: chuo-district">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            URLやAPIで使用される識別子です。半角英数字、ハイフン、アンダースコアのみ使用可能です。
                        </small>
                    </div>

                    <!-- 説明 -->
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="カテゴリーの説明や用途を入力">{{ old('description', $category->description) }}</textarea>
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
                            <option value="district" {{ old('type', $category->type) === 'district' ? 'selected' : '' }}>地区会</option>
                            <option value="role" {{ old('type', $category->type) === 'role' ? 'selected' : '' }}>役職</option>
                            <option value="other" {{ old('type', $category->type) === 'other' ? 'selected' : '' }}>その他</option>
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
                               value="{{ old('display_order', $category->display_order) }}"
                               min="0"
                               max="999"
                               step="1">
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            数値が小さいほど先頭に表示されます。
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
                                   {{ old('is_active', $category->is_active) == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>アクティブ</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            非アクティブにすると、新規にこのカテゴリーは選択できなくなりますが、既に紐づいているユーザーからは解除されません。
                        </small>
                    </div>

                    <!-- ユーザー紐づけ情報 -->
                    @if($category->users_count > 0)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            このカテゴリーには <strong>{{ number_format($category->users_count) }} 人</strong>のユーザーが紐づいています。
                        </div>
                    @endif

                    <!-- ボタン -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> 戻る
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> 更新
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 削除カード -->
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle"></i> 危険な操作
            </div>
            <div class="card-body">
                <h6>カテゴリーの削除</h6>
                <p class="mb-2">このカテゴリーを削除すると、以下の影響があります：</p>
                <ul>
                    <li>紐づいている全てのユーザーから、このカテゴリーが解除されます</li>
                    <li>この操作は取り消せません</li>
                </ul>
                
                @if($category->users_count > 0)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>{{ number_format($category->users_count) }} 人</strong>のユーザーに影響があります
                    </div>
                @endif

                <form method="POST" 
                      action="{{ route('admin.categories.destroy', $category) }}" 
                      onsubmit="return confirm('本当にこのカテゴリーを削除してもよろしいですか？\n{{ $category->users_count > 0 ? $category->users_count . '人のユーザーから解除されます。' : '' }}\nこの操作は取り消せません。');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> カテゴリーを削除
                    </button>
                </form>
            </div>
        </div>

        <!-- タイムスタンプ情報 -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row text-muted small">
                    <div class="col-md-6">
                        <i class="bi bi-clock-history"></i> 作成日時: {{ $category->created_at->format('Y/m/d H:i:s') }}
                    </div>
                    <div class="col-md-6">
                        <i class="bi bi-clock"></i> 更新日時: {{ $category->updated_at->format('Y/m/d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
