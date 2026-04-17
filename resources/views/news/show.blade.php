@extends('layouts.app')

@section('title', $news->title . ' - 松.net')

@section('content')
<div class="row">
    @include('mypage._sidebar')

    <!-- メインコンテンツ -->
    <div class="col-12 col-md-9">
        <div class="mb-3">
            <a href="{{ route('news.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> お知らせ一覧に戻る
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="mb-0">
                        @if($news->is_top_display)
                            <span class="badge me-1" style="background-color: #97bc62;">重要</span>
                        @endif
                        {{ $news->title }}
                    </h5>
                    <small class="text-white-50 text-nowrap ms-3">
                        {{ $news->published_at->format('Y年m月d日') }}
                    </small>
                </div>
            </div>
            <div class="card-body">
                @if($news->target_graduation_years && count($news->target_graduation_years) > 0)
                    <div class="mb-3">
                        <span class="badge bg-secondary">
                            <i class="bi bi-mortarboard"></i>
                            対象：{{ implode('・', array_map(fn($y) => $y.'年卒', $news->target_graduation_years)) }}
                        </span>
                    </div>
                @endif

                <div class="news-body" style="line-height: 1.8; white-space: pre-wrap;">{{ $news->body }}</div>

                @if($news->creator)
                    <hr>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-person"></i> 投稿者：{{ $news->creator->full_name }}
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('news.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> お知らせ一覧に戻る
            </a>
        </div>
    </div>
</div>
@endsection
