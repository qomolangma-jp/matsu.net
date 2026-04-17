@extends('layouts.admin')

@section('title', 'ニュース編集 - 松.net')
@section('page-title', 'ニュース編集')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> ニュース編集
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.news.update', $news->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title', $news->title) }}" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 本文 -->
                    <div class="mb-3">
                        <label for="body" class="form-label">本文 <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('body') is-invalid @enderror" 
                                  id="body" 
                                  name="body" 
                                  rows="10" 
                                  required>{{ old('body', $news->body) }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 対象卒業年度 -->
                    <div class="mb-3">
                        <label class="form-label">対象卒業年度</label>
                        <div class="form-text mb-2">
                            <i class="bi bi-info-circle"></i> 未選択の場合は全学年が対象になります
                        </div>
                        
                        @if(Auth::user()->role === 'master_admin')
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($graduationYears as $year)
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="target_graduation_years[]" 
                                               value="{{ $year }}" 
                                               id="year_{{ $year }}"
                                               {{ (is_array(old('target_graduation_years')) && in_array($year, old('target_graduation_years'))) || (is_array($news->target_graduation_years) && in_array($year, $news->target_graduation_years)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="year_{{ $year }}">
                                            {{ $year }}年（{{ $year - 1947 }}回期）
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                学年管理者は自学年（{{ Auth::user()->graduation_year }}年）のニュースのみ編集できます
                            </div>
                            <input type="hidden" name="target_graduation_years[]" value="{{ Auth::user()->graduation_year }}">
                        @endif
                        
                        @error('target_graduation_years')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- TOPページに掲載する -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_top_display" 
                                   name="is_top_display" 
                                   value="1"
                                   {{ old('is_top_display', $news->is_top_display) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_top_display">
                                <i class="bi bi-star"></i> TOPページに掲載する
                            </label>
                        </div>
                    </div>

                    <!-- LINE通知セクション -->
                    <div class="mb-4 p-3 rounded border border-success bg-light">
                        <h6 class="mb-3"><i class="bi bi-line text-success"></i> LINE通知</h6>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-success">{{ $lineSentCount }}</div>
                                <small class="text-muted">送信済み</small>
                            </div>
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-warning">{{ $lineUnsentCount }}</div>
                                <small class="text-muted">未送信</small>
                            </div>
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-secondary">{{ $lineTargetCount }}</div>
                                <small class="text-muted">対象合計</small>
                            </div>
                        </div>
                        @if($lineUnsentCount > 0)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox"
                                       id="send_line_to_unsent" name="send_line_to_unsent" value="1">
                                <label class="form-check-label" for="send_line_to_unsent">
                                    <i class="bi bi-send"></i>
                                    未送信の <strong>{{ $lineUnsentCount }}件</strong> のユーザーにLINE送信する
                                </label>
                            </div>
                        @else
                            <p class="text-success mb-2 small">
                                <i class="bi bi-check-circle-fill"></i> 全ての対象ユーザーに送信済みです
                            </p>
                        @endif
                        @if($lineSentCount > 0)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       id="send_line_resend_all" name="send_line_resend_all" value="1">
                                <label class="form-check-label text-warning" for="send_line_resend_all">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    全員（{{ $lineTargetCount }}件）に再送する（既送信ユーザーへも再送）
                                </label>
                            </div>
                        @endif
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> 更新する
                        </button>
                        <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary">
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
                        <i class="bi bi-person"></i> 作成者：{{ $news->creator?->full_name }}
                    </div>
                    <div class="col-md-6">
                        <i class="bi bi-clock"></i> 作成日時：{{ $news->created_at?->format('Y/m/d H:i') }}
                    </div>
                    @if($news->published_at)
                        <div class="col-md-6 mt-2">
                            <i class="bi bi-calendar-check"></i> 公開日時：{{ $news->published_at->format('Y/m/d H:i') }}
                        </div>
                    @endif
                    @if($news->is_line_notification)
                        <div class="col-md-6 mt-2">
                            <i class="bi bi-line"></i> <span class="badge bg-success">LINE送信済</span>
                        </div>
                    @endif
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
                <p class="small">既にLINE送信済みのニュースを編集しても、再度LINEは送信されません。</p>
                <p class="small">TOPページ掲載の設定は変更できます。</p>
            </div>
        </div>
    </div>
</div>
@endsection
