<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::user();

        if (!in_array($admin->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $userQuery = User::query();
        $eventQuery = Event::query();
        $newsQuery = News::query();

        // マスター管理者以外は自学年データのみ
        if ($admin->role !== 'master_admin') {
            $userQuery->where('graduation_year', $admin->graduation_year);
            $eventQuery->where('graduation_year', $admin->graduation_year);
            $newsQuery->where(function ($q) use ($admin) {
                $q->whereJsonContains('target_graduation_years', (string) $admin->graduation_year)
                  ->orWhereJsonContains('target_graduation_years', (int) $admin->graduation_year);
            });
        }

        // 最新登録ユーザー5名
        $latestUsers = (clone $userQuery)
            ->with('categories')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 未承認ユーザー
        $pendingUsers = (clone $userQuery)
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        // 最新イベント5件
        $latestEvents = (clone $eventQuery)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 最新ニュース5件
        $latestNews = (clone $newsQuery)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 統計
        $stats = [
            'total_users'    => (clone $userQuery)->count(),
            'approved_users' => (clone $userQuery)->where('approval_status', 'approved')->count(),
            'pending_users'  => (clone $userQuery)->where('approval_status', 'pending')->count(),
            'total_events'   => (clone $eventQuery)->count(),
            'total_news'     => (clone $newsQuery)->count(),
        ];

        return view('admin.dashboard', compact(
            'latestUsers', 'pendingUsers', 'latestEvents', 'latestNews', 'stats'
        ));
    }
}
