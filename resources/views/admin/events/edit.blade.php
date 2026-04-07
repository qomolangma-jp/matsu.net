@extends('layouts.admin')

@section('title', 'イベント編集 - 松.net')
@section('page-title', 'イベント編集')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> イベント編集
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.events.update', $event->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">イベント名 <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title', $event->title) }}" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 説明 -->
                    <div class="mb-3">
                        <label for="description" class="form-label">イベント内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="6" 
                                  required>{{ old('description', $event->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <!-- 開催日時 -->
                        <div class="col-md-6 mb-3">
                            <label for="event_date" class="form-label">開催日時 <span class="text-danger">*</span></label>
                            <input type="datetime-local" 
                                   class="form-control @error('event_date') is-invalid @enderror" 
                                   id="event_date" 
                                   name="event_date" 
                                   value="{{ old('event_date', $event->event_date?->format('Y-m-d\TH:i')) }}" 
                                   required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 開催場所 -->
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">開催場所</label>
                            <input type="text" 
                                   class="form-control @error('location') is-invalid @enderror" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location', $event->location) }}">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- 募集締切 -->
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">募集締切日</label>
                            <input type="datetime-local" 
                                   class="form-control @error('deadline') is-invalid @enderror" 
                                   id="deadline" 
                                   name="deadline" 
                                   value="{{ old('deadline', $event->deadline?->format('Y-m-d\TH:i')) }}">
                            @error('deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 定員 -->
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">定員</label>
                            <input type="number" 
                                   class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" 
                                   name="capacity" 
                                   value="{{ old('capacity', $event->capacity) }}" 
                                   min="1">
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 対象学年（編集不可） -->
                    <div class="mb-3">
                        <label class="form-label">対象学年</label>
                        <div class="alert alert-secondary">
                            <i class="bi bi-lock"></i> {{ $event->target_year_display }}
                            <small class="d-block mt-1">※ 対象学年は作成後に変更できません</small>
                        </div>
                    </div>

                    <hr>

                    <!-- 公開状態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_published" 
                                   name="is_published" 
                                   value="1"
                                   {{ old('is_published', $event->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">
                                公開する
                            </label>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> 更新する
                        </button>
                        <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> キャンセル
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 作成情報 -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row small text-muted">
                    <div class="col-md-6">
                        <i class="bi bi-person"></i> 作成者：{{ $event->creator?->full_name }}
                    </div>
                    <div class="col-md-6">
                        <i class="bi bi-clock"></i> 作成日時：{{ $event->created_at?->format('Y/m/d H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> 編集について
            </div>
            <div class="card-body">
                <p class="small">対象学年は作成後に変更できません。間違えた場合は削除して作り直してください。</p>
                <p class="small">既に出欠回答がある場合、日時や場所を変更すると参加者に影響があります。</p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-list-check"></i> 出欠状況
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-outline-success">
                        <i class="bi bi-eye"></i> 出欠状況を見る
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
