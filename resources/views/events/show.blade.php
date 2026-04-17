@extends('layouts.app')

@section('title', $event->title . ' - 松.net')

@section('content')
<div class="row">
    @include('mypage._sidebar')

    <!-- メインコンテンツ -->
    <div class="col-12 col-md-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event"></i> {{ $event->title }}
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th class="text-muted small w-25">開催日時</th>
                            <td>{{ $event->event_date->format('Y年m月d日（D）H:i') }}</td>
                        </tr>
                        @if($event->location)
                        <tr>
                            <th class="text-muted small">開催場所</th>
                            <td>{{ $event->location }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted small">対象学年</th>
                            <td>
                                <span class="badge" style="background-color: #2c5f2d;">
                                    {{ $event->target_year_display }}
                                </span>
                            </td>
                        </tr>
                        @if($event->deadline)
                        <tr>
                            <th class="text-muted small">申込締切</th>
                            <td>
                                {{ $event->deadline->format('Y年m月d日 H:i') }}
                                @if($event->deadline->isFuture())
                                    <span class="badge bg-warning text-dark ms-1">受付中</span>
                                @else
                                    <span class="badge bg-secondary ms-1">締切済</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($event->capacity)
                        <tr>
                            <th class="text-muted small">定員</th>
                            <td>{{ $event->capacity }}名</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                @if($event->description)
                    <hr>
                    <div class="mt-3">
                        <h6 class="text-muted">イベント詳細</h6>
                        <div class="event-description">
                            {!! nl2br(e($event->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- 参加回答フォーム --}}
                <hr>
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @php
                    $canRespond = !$event->deadline || $event->deadline->isFuture();
                @endphp

                <div class="mt-3">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-reply"></i> 参加回答
                        @if($attendance)
                            <span class="badge {{ $attendance->status_badge_class }} ms-2">
                                現在: {{ $attendance->status_label }}
                            </span>
                        @endif
                    </h6>

                    @if($canRespond)
                        <form action="{{ route('events.respond', $event) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-semibold">出欠 <span class="text-danger">*</span></label>
                                @php $currentStatus = old('status', $attendance?->status ?? ''); @endphp
                                <div class="d-flex gap-3 flex-wrap mt-1">

                                    {{-- 出席 --}}
                                    <label for="status_attending"
                                           class="status-card border rounded-3 p-3 text-center {{ $currentStatus === 'attending' ? 'selected-attending' : '' }}"
                                           style="min-width:110px; cursor:pointer;">
                                        <input type="radio" class="d-none status-radio" name="status"
                                               id="status_attending" value="attending"
                                               {{ $currentStatus === 'attending' ? 'checked' : '' }} required>
                                        <div class="fs-2 mb-1">✅</div>
                                        <div class="fw-bold">出席</div>
                                    </label>

                                    {{-- 欠席 --}}
                                    <label for="status_absent"
                                           class="status-card border rounded-3 p-3 text-center {{ $currentStatus === 'absent' ? 'selected-absent' : '' }}"
                                           style="min-width:110px; cursor:pointer;">
                                        <input type="radio" class="d-none status-radio" name="status"
                                               id="status_absent" value="absent"
                                               {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                        <div class="fs-2 mb-1">❌</div>
                                        <div class="fw-bold">欠席</div>
                                    </label>

                                    {{-- 未定 --}}
                                    <label for="status_pending"
                                           class="status-card border rounded-3 p-3 text-center {{ $currentStatus === 'pending' ? 'selected-pending' : '' }}"
                                           style="min-width:110px; cursor:pointer;">
                                        <input type="radio" class="d-none status-radio" name="status"
                                               id="status_pending" value="pending"
                                               {{ $currentStatus === 'pending' ? 'checked' : '' }}>
                                        <div class="fs-2 mb-1">🤔</div>
                                        <div class="fw-bold">未定</div>
                                    </label>

                                </div>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" style="max-width: 200px;">
                                <label for="guests_count" class="form-label">同伴者数</label>
                                <input type="number" class="form-control" id="guests_count" name="guests_count"
                                       min="0" max="10"
                                       value="{{ old('guests_count', $attendance?->guests_count ?? 0) }}">
                            </div>

                            <div class="mb-3" style="max-width: 500px;">
                                <label for="remarks" class="form-label">備考・メッセージ</label>
                                <textarea class="form-control" id="remarks" name="remarks"
                                          rows="3" maxlength="500"
                                          placeholder="アレルギーや連絡事項など">{{ old('remarks', $attendance?->remarks ?? '') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle"></i>
                                {{ $attendance ? '回答を更新する' : '回答する' }}
                            </button>
                        </form>
                    @else
                        <div class="alert alert-secondary">
                            <i class="bi bi-lock"></i> 申込締切が過ぎているため、回答できません。
                            @if($attendance)
                                <br><small>あなたの回答: <strong>{{ $attendance->status_label }}</strong></small>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> イベント一覧に戻る
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.status-card {
    background: #fff;
    border-color: #dee2e6 !important;
    transition: transform 0.1s, box-shadow 0.1s, border-color 0.1s;
    user-select: none;
}
.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.selected-attending {
    border-color: #198754 !important;
    background-color: #d1e7dd !important;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.3) !important;
}
.selected-absent {
    border-color: #dc3545 !important;
    background-color: #f8d7da !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3) !important;
}
.selected-pending {
    border-color: #ffc107 !important;
    background-color: #fff3cd !important;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3) !important;
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.status-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        // 全カードのselectedクラスをリセット
        document.querySelectorAll('.status-card').forEach(function(card) {
            card.classList.remove('selected-attending', 'selected-absent', 'selected-pending');
        });
        // 選択されたカードにクラス付与
        var label = document.querySelector('label[for="' + this.id + '"]');
        if (label) {
            var classMap = {
                'status_attending': 'selected-attending',
                'status_absent':    'selected-absent',
                'status_pending':   'selected-pending'
            };
            label.classList.add(classMap[this.id]);
        }
    });
});
</script>
@endpush
@endsection
