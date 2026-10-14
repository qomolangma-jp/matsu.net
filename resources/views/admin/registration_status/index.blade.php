@extends('layouts.admin')

@section('title', '登録状況 - 松高.net 管理画面')
@section('page-title', '登録状況')

@section('content')

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-person-x"></i> 同窓生登録のない年代
                <span class="badge bg-secondary">{{ $noRegistration->count() }}</span>
            </div>
            <div class="card-body">
                @forelse($noRegistration as $r)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $r->term }}回期（{{ $r->year }}年度）</span>
                @empty
                    <span class="text-muted">すべての年代に登録があります。</span>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-person-badge"></i> 学年管理者が登録のない年代
                <span class="badge bg-secondary">{{ $noYearAdmin->count() }}</span>
            </div>
            <div class="card-body">
                @forelse($noYearAdmin as $r)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $r->term }}回期（{{ $r->year }}年度）</span>
                @empty
                    <span class="text-muted">すべての年代に学年管理者がいます。</span>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-bar-chart"></i> 各年代の登録数一覧
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>回期</th>
                    <th>卒業年度</th>
                    <th class="text-end">参照名簿数</th>
                    <th class="text-end">登録総数</th>
                    <th class="text-end">承認済み</th>
                    <th class="text-end">承認待ち</th>
                    <th class="text-end">学年管理者</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr class="{{ $r->approved === 0 ? 'table-warning' : '' }}">
                        <td>{{ $r->term }}回期</td>
                        <td>
                            <a href="{{ route('admin.users.index', ['graduation_year' => $r->year]) }}">{{ $r->year }}年度</a>
                        </td>
                        <td class="text-end">{{ number_format($r->roster) }}</td>
                        <td class="text-end">{{ number_format($r->total) }}</td>
                        <td class="text-end">{{ number_format($r->approved) }}</td>
                        <td class="text-end">{{ number_format($r->pending) }}</td>
                        <td class="text-end">
                            @if($r->year_admins === 0)
                                <span class="text-danger">なし</span>
                            @else
                                {{ $r->year_admins }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="2">合計</td>
                    <td class="text-end">{{ number_format($rows->sum('roster')) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('total')) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('approved')) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('pending')) }}</td>
                    <td class="text-end">{{ $rows->sum('year_admins') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
