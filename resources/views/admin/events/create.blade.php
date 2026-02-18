@extends('layouts.admin')

@section('title', 'イベント作成 - 松.net')
@section('page-title', 'イベント作成')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-event"></i> イベント作成
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.events.store') }}">
                    @csrf

                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">イベント名 <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title') }}" 
                               placeholder="例：第75回期同窓会"
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
                                  required>{{ old('description') }}</textarea>
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
                                   value="{{ old('event_date') }}" 
                                   required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 開催場所 -->
                        <div class="col-md-6 mb-3">
                            <label for="event_location" class="form-label">開催場所</label>
                            <input type="text" 
                                   class="form-control @error('event_location') is-invalid @enderror" 
                                   id="event_location" 
                                   name="event_location" 
                                   value="{{ old('event_location') }}" 
                                   placeholder="例：東京プリンスホテル">
                            @error('event_location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- 募集締切 -->
                        <div class="col-md-6 mb-3">
                            <label for="registration_deadline" class="form-label">募集締切日</label>
                            <input type="datetime-local" 
                                   class="form-control @error('registration_deadline') is-invalid @enderror" 
                                   id="registration_deadline" 
                                   name="registration_deadline" 
                                   value="{{ old('registration_deadline') }}">
                            @error('registration_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 定員 -->
                        <div class="col-md-6 mb-3">
                            <label for="max_participants" class="form-label">定員</label>
                            <input type="number" 
                                   class="form-control @error('max_participants') is-invalid @enderror" 
                                   id="max_participants" 
                                   name="max_participants" 
                                   value="{{ old('max_participants') }}" 
                                   min="1"
                                   placeholder="未入力の場合、定員なし">
                            @error('max_participants')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 対象学年 -->
                    <div class="mb-3">
                        <label for="target_graduation_year" class="form-label">対象学年</label>
                        
                        @if(Auth::user()->role === 'master_admin')
                            <select class="form-select @error('target_graduation_year') is-invalid @enderror" 
                                    id="target_graduation_year" 
                                    name="target_graduation_year">
                                <option value="">全体同窓会（全学年対象）</option>
                                @foreach($graduationYears as $year)
                                    <option value="{{ $year }}" {{ old('target_graduation_year') == $year ? 'selected' : '' }}>
                                        {{ $year }}年（{{ $year - 1947 }}回期）
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 全体同窓会の場合は未選択のままにしてください
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                学年管理者は自学年（{{ Auth::user()->graduation_year }}年）のイベントのみ作成できます
                            </div>
                            <input type="hidden" name="target_graduation_year" value="{{ Auth::user()->graduation_year }}">
                        @endif
                        
                        @error('target_graduation_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <!-- すぐに公開する -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_published" 
                                   name="is_published" 
                                   value="1"
                                   {{ old('is_published', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">
                                すぐに公開する
                            </label>
                        </div>
                        <div class="form-text">
                            チェックを外すと下書きとして保存されます
                        </div>
                    </div>

                    <!-- LINEで通知する -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="send_line_notification" 
                                   name="send_line_notification" 
                                   value="1"
                                   {{ old('send_line_notification') ? 'checked' : '' }}>
                            <label class="form-check-label" for="send_line_notification">
                                <i class="bi bi-line"></i> LINEで通知する
                            </label>
                        </div>
                        <div class="form-text">
                            対象ユーザーにLINEメッセージを送信します（公開時のみ）
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> 作成する
                        </button>
                        <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> キャンセル
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightbulb"></i> ヒント
            </div>
            <div class="card-body">
                <h6>対象学年について</h6>
                <p class="small mb-3">
                    <strong>全体同窓会：</strong> 全学年を対象としたイベント（例：開校記念同窓会）<br>
                    <strong>学年イベント：</strong> 特定の学年を対象としたイベント（例：第◎◎回期同窓会）
                </p>

                <h6>募集締切と定員について</h6>
                <p class="small mb-3">
                    締切日を過ぎると自動的に出欠回答を受け付けなくなります。定員に達した場合も同様です。
                </p>

                <h6>LINE通知について</h6>
                <p class="small">
                    公開と同時にLINE通知を送信する場合はチェックを入れてください。後から通知することもできます。
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
