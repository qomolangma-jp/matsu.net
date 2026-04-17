<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\News;

class DashboardController extends Controller
{
    public function index()
    {
        // 最新登録ユーザー5名
        $latestUsers = User::with('categories')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 未承認ユーザー
        $pendingUsers = User::where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        // 最新イベント5件
        $latestEvents = Event::orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 最新ニュース5件
        $latestNews = News::orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 統計
        $stats = [
            'total_users'    => User::count(),
            'approved_users' => User::where('approval_status', 'approved')->count(),
            'pending_users'  => User::where('approval_status', 'pending')->count(),
            'total_events'   => Event::count(),
            'total_news'     => News::count(),
        ];

        return view('admin.dashboard', compact(
            'latestUsers', 'pendingUsers', 'latestEvents', 'latestNews', 'stats'
        ));
    }
}
