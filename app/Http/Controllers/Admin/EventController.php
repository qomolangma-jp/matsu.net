<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Models\Attendance;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    private LineMessagingService $lineService;

    public function __construct(LineMessagingService $lineService)
    {
        $this->lineService = $lineService;
    }

    /**
     * イベント一覧
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $query = Event::with('creator')->filterByPermission($user);

        // ステータスフィルタ
        $status = $request->input('status');
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        $events = $query->orderBy('event_date', 'desc')->paginate(20);

        return view('admin.events.index', compact('events'));
    }

    /**
     * イベント作成画面
     */
    public function create()
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $graduationYears = $this->getGraduationYearsList($user);

        return view('admin.events.create', compact('graduationYears'));
    }

    /**
     * イベント保存
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'event_location' => 'nullable|string|max:255',
            'registration_deadline' => 'nullable|date|before:event_date',
            'max_participants' => 'nullable|integer|min:1',
            'target_graduation_year' => 'nullable|integer|min:1948',
            'is_published' => 'boolean',
            'send_line_notification' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // 学年管理者は自学年のイベントのみ作成可能
            $targetYear = $request->input('target_graduation_year');
            if ($user->role === 'year_admin') {
                if ($targetYear && $targetYear != $user->graduation_year) {
                    return back()->withErrors(['target_graduation_year' => '自学年以外は選択できません。'])->withInput();
                }
                // NULLの場合は自学年に設定
                $targetYear = $user->graduation_year;
            }

            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date'],
                'event_location' => $validated['event_location'],
                'registration_deadline' => $validated['registration_deadline'],
                'max_participants' => $validated['max_participants'],
                'target_graduation_year' => $targetYear,
                'is_published' => $request->boolean('is_published'),
                'created_by' => $user->id,
            ]);

            // LINE通知送信
            if ($request->boolean('send_line_notification') && $request->boolean('is_published')) {
                $this->sendLineNotification($event);
            }

            DB::commit();

            return redirect()->route('admin.events.index')
                ->with('success', 'イベントを作成しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('イベント作成エラー', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'イベントの作成に失敗しました。'])->withInput();
        }
    }

    /**
     * イベント詳細・出欠状況
     */
    public function show(Event $event)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年のイベントのみ閲覧可能
        if ($user->role === 'year_admin' && $event->target_graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは閲覧できません。');
        }

        // 出欠状況
        $attendances = Attendance::where('event_id', $event->id)
            ->with('user')
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // ステータス別に集計
        $attendingUsers = $attendances->where('status', 'attending');
        $absentUsers = $attendances->where('status', 'absent');

        // 対象ユーザー数を取得
        $targetUsersQuery = User::approved();
        if ($event->target_graduation_year) {
            $targetUsersQuery->where('graduation_year', $event->target_graduation_year);
        }
        $totalTargetUsers = $targetUsersQuery->count();

        // 未回答ユーザーを取得
        $respondedUserIds = $attendances->pluck('user_id')->toArray();
        $pendingUsers = $targetUsersQuery->whereNotIn('id', $respondedUserIds)->get();

        $stats = [
            'total_target' => $totalTargetUsers,
            'attending' => $attendingUsers->count(),
            'absent' => $absentUsers->count(),
            'pending' => $pendingUsers->count(),
            'response_rate' => $totalTargetUsers > 0 
                ? round(($attendances->count() / $totalTargetUsers) * 100, 1) 
                : 0,
        ];

        return view('admin.events.show', compact(
            'event',
            'attendingUsers',
            'absentUsers',
            'pendingUsers',
            'stats'
        ));
    }

    /**
     * イベント編集画面
     */
    public function edit(Event $event)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年のイベントのみ編集可能
        if ($user->role === 'year_admin' && $event->target_graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは編集できません。');
        }

        $graduationYears = $this->getGraduationYearsList($user);

        return view('admin.events.edit', compact('event', 'graduationYears'));
    }

    /**
     * イベント更新
     */
    public function update(Request $request, Event $event)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年のイベントのみ更新可能
        if ($user->role === 'year_admin' && $event->target_graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは更新できません。');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'event_location' => 'nullable|string|max:255',
            'registration_deadline' => 'nullable|date|before:event_date',
            'max_participants' => 'nullable|integer|min:1',
            'is_published' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $event->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date'],
                'event_location' => $validated['event_location'],
                'registration_deadline' => $validated['registration_deadline'],
                'max_participants' => $validated['max_participants'],
                'is_published' => $request->boolean('is_published'),
            ]);

            DB::commit();

            return redirect()->route('admin.events.show', $event->id)
                ->with('success', 'イベントを更新しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('イベント更新エラー', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'イベントの更新に失敗しました。'])->withInput();
        }
    }

    /**
     * イベント削除
     */
    public function destroy(Event $event)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年のイベントのみ削除可能
        if ($user->role === 'year_admin' && $event->target_graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは削除できません。');
        }

        try {
            $event->delete();

            return redirect()->route('admin.events.index')
                ->with('success', 'イベントを削除しました。');

        } catch (\Exception $e) {
            Log::error('イベント削除エラー', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['error' => 'イベントの削除に失敗しました。']);
        }
    }

    /**
     * 出欠データCSVエクスポート
     */
    public function exportAttendances(Event $event)
    {
        $user = Auth::user();

        // 権限チェック
        if (!in_array($user->role, ['year_admin', 'master_admin'])) {
            abort(403, '管理者権限が必要です。');
        }

        // 学年管理者は自学年のイベントのみエクスポート可能
        if ($user->role === 'year_admin' && $event->target_graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントはエクスポートできません。');
        }

        $attendances = Attendance::where('event_id', $event->id)
            ->with('user')
            ->orderBy('status', 'asc')
            ->get();

        $filename = 'event_' . $event->id . '_attendances_' . date('YmdHis') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($event, $attendances) {
            $file = fopen('php://output', 'w');
            
            // BOM追加（Excel対応）
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // イベント情報
            fputcsv($file, ['イベント名', $event->title]);
            fputcsv($file, ['開催日時', $event->event_date?->format('Y-m-d H:i')]);
            fputcsv($file, ['開催場所', $event->event_location]);
            fputcsv($file, []);
            
            // ヘッダー行
            fputcsv($file, [
                'ID',
                '氏名',
                '氏名（カナ）',
                '卒業年度',
                '回期',
                'メールアドレス',
                'ステータス',
                '備考',
                '回答日時',
            ]);

            // データ行
            foreach ($attendances as $attendance) {
                $user = $attendance->user;
                fputcsv($file, [
                    $user->id,
                    $user->full_name,
                    $user->last_name_kana . ' ' . $user->first_name_kana,
                    $user->graduation_year,
                    $user->graduation_year - 1947,
                    $user->email,
                    $attendance->status_label,
                    $attendance->note,
                    $attendance->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * LINE通知送信
     */
    private function sendLineNotification(Event $event)
    {
        // 対象ユーザーを取得
        $usersQuery = User::approved()->whereNotNull('line_id');

        if ($event->target_graduation_year) {
            $usersQuery->where('graduation_year', $event->target_graduation_year);
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            Log::warning('LINE送信対象ユーザーが見つかりません', [
                'event_id' => $event->id,
                'target_year' => $event->target_graduation_year,
            ]);
            return;
        }

        // LINE送信
        $result = $this->lineService->sendEventNotification($event, $users);

        Log::info('LINE通知送信完了', [
            'event_id' => $event->id,
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
        ]);
    }

    /**
     * 卒業年度リスト取得
     */
    private function getGraduationYearsList(User $user): array
    {
        if ($user->role === 'master_admin') {
            return User::select('graduation_year')
                ->distinct()
                ->whereNotNull('graduation_year')
                ->orderBy('graduation_year', 'desc')
                ->pluck('graduation_year')
                ->toArray();
        }

        // 学年管理者は自学年のみ
        return [$user->graduation_year];
    }
}
