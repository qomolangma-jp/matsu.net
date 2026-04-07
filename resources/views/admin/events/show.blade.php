@extends('layouts.admin')

@section('title', '出欠状況 - ' . $event->title . ' - 松.net')
@section('page-title', '出欠状況')

@section('top-actions')
    <div class="btn-group">
        <a href="{{ route('admin.events.export-attendances', $event->id) }}" class="btn btn-success">
            <i class="bi bi-download"></i> CSVダウンロード
        </a>
        <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> イベント編集
        </a>
        <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 一覧に戻る
        </a>
    </div>
@endsection

@section('content')
<!-- イベント情報 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> {{ $event->title }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th style="width: 120px;"><i class="bi bi-calendar"></i> 開催日時</th>
                        <td>{{ $event->event_date?->format('Y年m月d日 H:i') }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-geo-alt"></i> 開催場所</th>
                        <td>{{ $event->location ?? '未定' }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-people"></i> 対象学年</th>
                        <td><span class="badge {{ $event->graduation_year ? 'bg-info' : 'bg-primary' }}">{{ $event->target_year_display }}</span></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th style="width: 120px;"><i class="bi bi-clock"></i> 募集締切</th>
                        <td>{{ $event->deadline?->format('Y年m月d日 H:i') ?? '設定なし' }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-hash"></i> 定員</th>
                        <td>{{ $event->capacity ? $event->capacity . '名' : '制限なし' }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-eye"></i> 公開状態</th>
                        <td>
                            @if($event->is_published)
                                <span class="badge bg-success">公開中</span>
                            @else
                                <span class="badge bg-warning text-dark">下書き</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <strong>イベント内容：</strong>
            <p class="mt-2">{{ $event->description }}</p>
        </div>
    </div>
</div>

<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #6c757d, #495057);">
            <small>対象者数</small>
            <h3>{{ number_format($stats['total_target']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <small>出席</small>
            <h3>{{ number_format($stats['attending']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
            <small>欠席</small>
            <h3>{{ number_format($stats['absent']) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <small>未回答</small>
            <h3>{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>
</div>

<!-- 回答率 -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span><strong>回答率</strong></span>
            <span><strong>{{ $stats['response_rate'] }}%</strong> ({{ $stats['attending'] + $stats['absent'] }} / {{ $stats['total_target'] }})</span>
        </div>
        <div class="progress" style="height: 25px;">
            @php
                $attendingPercent = $stats['total_target'] > 0 ? ($stats['attending'] / $stats['total_target']) * 100 : 0;
                $absentPercent = $stats['total_target'] > 0 ? ($stats['absent'] / $stats['total_target']) * 100 : 0;
            @endphp
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $attendingPercent }}%" title="出席 {{ $stats['attending'] }}名">
                @if($attendingPercent > 10){{ $stats['attending'] }}@endif
            </div>
            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $absentPercent }}%" title="欠席 {{ $stats['absent'] }}名">
                @if($absentPercent > 10){{ $stats['absent'] }}@endif
            </div>
        </div>
    </div>
</div>

<!-- タブ -->
<ul class="nav nav-tabs mb-3" id="attendanceTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="attending-tab" data-bs-toggle="tab" data-bs-target="#attending" type="button" role="tab">
            <i class="bi bi-check-circle"></i> 出席 <span class="badge bg-success">{{ $stats['attending'] }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="absent-tab" data-bs-toggle="tab" data-bs-target="#absent" type="button" role="tab">
            <i class="bi bi-x-circle"></i> 欠席 <span class="badge bg-secondary">{{ $stats['absent'] }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
            <i class="bi bi-clock-history"></i> 未回答 <span class="badge bg-warning text-dark">{{ $stats['pending'] }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="attendanceTabsContent">
    <!-- 出席者 -->
    <div class="tab-pane fade show active" id="attending" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>氏名</th>
                                <th>卒業年度</th>
                                <th>メールアドレス</th>
                                <th>備考</th>
                                <th>回答日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendingUsers as $attendance)
                                <tr>
                                    <td>{{ $attendance->user->id }}</td>
                                    <td>
                                        <strong>{{ $attendance->user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $attendance->user->last_name_kana }} {{ $attendance->user->first_name_kana }}</small>
                                    </td>
                                    <td>
                                        {{ $attendance->user->graduation_year }}年<br>
                                        <small class="text-muted">{{ $attendance->user->graduation_year - 1947 }}回期</small>
                                    </td>
                                    <td>
                                        @if($attendance->user->email)
                                            <a href="mailto:{{ $attendance->user->email }}">{{ $attendance->user->email }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->note)
                                            <small>{{ $attendance->note }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $attendance->created_at->format('Y/m/d H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">出席者はいません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 欠席者 -->
    <div class="tab-pane fade" id="absent" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>氏名</th>
                                <th>卒業年度</th>
                                <th>メールアドレス</th>
                                <th>備考</th>
                                <th>回答日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absentUsers as $attendance)
                                <tr>
                                    <td>{{ $attendance->user->id }}</td>
                                    <td>
                                        <strong>{{ $attendance->user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $attendance->user->last_name_kana }} {{ $attendance->user->first_name_kana }}</small>
                                    </td>
                                    <td>
                                        {{ $attendance->user->graduation_year }}年<br>
                                        <small class="text-muted">{{ $attendance->user->graduation_year - 1947 }}回期</small>
                                    </td>
                                    <td>
                                        @if($attendance->user->email)
                                            <a href="mailto:{{ $attendance->user->email }}">{{ $attendance->user->email }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->note)
                                            <small>{{ $attendance->note }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $attendance->created_at->format('Y/m/d H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">欠席者はいません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 未回答者 -->
    <div class="tab-pane fade" id="pending" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>氏名</th>
                                <th>卒業年度</th>
                                <th>メールアドレス</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingUsers as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <strong>{{ $user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $user->last_name_kana }} {{ $user->first_name_kana }}</small>
                                    </td>
                                    <td>
                                        {{ $user->graduation_year }}年<br>
                                        <small class="text-muted">{{ $user->graduation_year - 1947 }}回期</small>
                                    </td>
                                    <td>
                                        @if($user->email)
                                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">未回答者はいません。全員が回答済みです！</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
