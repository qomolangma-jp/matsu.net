@extends('layouts.admin')

@section('title', 'ニュース作成 - 松.net')
@section('page-title', 'ニュース作成')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-newspaper"></i> ニュース作成
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.news.store') }}">
                    @csrf

                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title') }}" 
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
                                  required>{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 改行はそのまま表示されます
                        </div>
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
                                               {{ is_array(old('target_graduation_years')) && in_array($year, old('target_graduation_years')) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="year_{{ $year }}">
                                            {{ $year }}年（{{ $year - 1947 }}回期）
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                学年管理者は自学年（{{ Auth::user()->graduation_year }}年）のニュースのみ作成できます
                            </div>
                            <input type="hidden" name="target_graduation_years[]" value="{{ Auth::user()->graduation_year }}">
                        @endif
                        
                        @error('target_graduation_years')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- LINEで通知する -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_line_notification" 
                                   name="is_line_notification" 
                                   value="1"
                                   {{ old('is_line_notification') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_line_notification">
                                <i class="bi bi-line"></i> LINEで通知する
                            </label>
                        </div>
                        <div class="form-text">
                            対象ユーザーにLINEメッセージを送信します
                        </div>
                    </div>

                    <!-- TOPページに掲載する -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_top_display" 
                                   name="is_top_display" 
                                   value="1"
                                   {{ old('is_top_display') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_top_display">
                                <i class="bi bi-star"></i> TOPページに掲載する
                            </label>
                        </div>
                    </div>

                    <!-- すぐに公開する -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="publish_now" 
                                   name="publish_now" 
                                   value="1"
                                   {{ old('publish_now', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="publish_now">
                                すぐに公開する
                            </label>
                        </div>
                        <div class="form-text">
                            チェックを外すと下書きとして保存されます
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> 作成する
                        </button>
                        <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary">
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
                <h6>LINEで通知する</h6>
                <p class="small">対象ユーザーのLINEにメッセージが送信されます。重要なお知らせの場合にご利用ください。</p>

                <h6>TOPページに掲載する</h6>
                <p class="small">ユーザーがログイン後に表示されるトップページにニュースが掲載されます。</p>

                <h6>対象卒業年度</h6>
                <p class="small">特定の学年のみに表示したい場合は、対象卒業年度を選択してください。未選択の場合は全学年が対象になります。</p>
            </div>
        </div>
    </div>
</div>
@endsection
