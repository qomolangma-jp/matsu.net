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

        $query = Event::with('creator')
            ->withCount('lineNotificationLogs as line_sent_count')
            ->filterByPermission($user);

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
            'location' => 'nullable|string|max:255',
            'deadline' => 'nullable|date|before:event_date',
            'capacity' => 'nullable|integer|min:1',
            'graduation_year' => 'nullable|integer|min:1948',
            'is_published' => 'boolean',
            'send_line_notification' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // 学年管理者は自学年のイベントのみ作成可能
            $targetYear = $request->input('graduation_year');
            if ($user->role === 'year_admin') {
                if ($targetYear && $targetYear != $user->graduation_year) {
                    return back()->withErrors(['graduation_year' => '自学年以外は選択できません。'])->withInput();
                }
                // NULLの場合は自学年に設定
                $targetYear = $user->graduation_year;
            }

            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date'],
                'location' => $validated['location'],
                'deadline' => $validated['deadline'],
                'capacity' => $validated['capacity'],
                'graduation_year' => $targetYear,
                'is_published' => $request->boolean('is_published'),
                'created_by' => $user->id,
            ]);

            // LINE通知送信
            $lineMsg = '';
            if ($request->boolean('send_line_notification') && $request->boolean('is_published')) {
                $result = $this->lineService->sendNotification($event, false);
                $lineMsg = "LINE通知: {$result['success_count']}件送信";
                if ($result['failure_count'] > 0) {
                    $lineMsg .= "（{$result['failure_count']}件失敗）";
                }
                Log::info('イベントLINE送信完了', [
                    'event_id'      => $event->id,
                    'success_count' => $result['success_count'],
                    'failure_count' => $result['failure_count'],
                ]);
            }

            DB::commit();

            $successMsg = 'イベントを作成しました。' . ($lineMsg ? ' ' . $lineMsg : '');
            return redirect()->route('admin.events.index')
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('イベント作成エラー', [
                'error'   => $e->getMessage(),
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
        if ($user->role === 'year_admin' && $event->graduation_year != $user->graduation_year) {
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
        if ($event->graduation_year) {
            $targetUsersQuery->where('graduation_year', $event->graduation_year);
        }
        $totalTargetUsers = $targetUsersQuery->count();

        // 未回答ユーザーを取得
        $respondedUserIds = $attendances->pluck('user_id')->toArray();
        $pendingUsers = $targetUsersQuery->whereNotIn('id', $respondedUserIds)->get();

        $stats = [
            'total_target'  => $totalTargetUsers,
            'attending'     => $attendingUsers->count(),
            'absent'        => $absentUsers->count(),
            'pending'       => $pendingUsers->count(),
            'response_rate' => $totalTargetUsers > 0
                ? round(($attendances->count() / $totalTargetUsers) * 100, 1)
                : 0,
        ];

        // LINE送信件数
        $lineSentCount = $event->lineNotificationLogs()->distinct('user_id')->count('user_id');

        return view('admin.events.show', compact(
            'event',
            'attendingUsers',
            'absentUsers',
            'pendingUsers',
            'stats',
            'lineSentCount'
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
        if ($user->role === 'year_admin' && $event->graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは編集できません。');
        }

        $graduationYears = $this->getGraduationYearsList($user);

        // LINE送信統計
        $lineSentCount   = $event->lineNotificationLogs()->distinct('user_id')->count('user_id');
        $targetUsersQuery = User::approved()->whereNotNull('line_id');
        if ($event->graduation_year) {
            $targetUsersQuery->where('graduation_year', $event->graduation_year);
        }
        $lineTargetCount = $targetUsersQuery->count();
        $lineUnsentCount = max(0, $lineTargetCount - $lineSentCount);

        return view('admin.events.edit', compact('event', 'graduationYears', 'lineSentCount', 'lineTargetCount', 'lineUnsentCount'));
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
        if ($user->role === 'year_admin' && $event->graduation_year != $user->graduation_year) {
            abort(403, '他学年のイベントは更新できません。');
        }

        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'event_date'           => 'required|date',
            'location'             => 'nullable|string|max:255',
            'deadline'             => 'nullable|date|before:event_date',
            'capacity'             => 'nullable|integer|min:1',
            'is_published'         => 'boolean',
            'send_line_to_unsent'  => 'nullable|boolean',
            'send_line_resend_all' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $event->update([
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'event_date'  => $validated['event_date'],
                'location'    => $validated['location'],
                'deadline'    => $validated['deadline'],
                'capacity'    => $validated['capacity'],
                'is_published'=> $request->boolean('is_published'),
            ]);

            // LINE送信処理（更新時）
            $lineMsg = '';
            if ($request->boolean('send_line_resend_all')) {
                $result  = $this->lineService->sendNotification($event, true);
                $lineMsg = "LINE再送: {$result['success_count']}件";
                if ($result['failure_count'] > 0) { $lineMsg .= "（{$result['failure_count']}件失敗）"; }
            } elseif ($request->boolean('send_line_to_unsent')) {
                $result  = $this->lineService->sendNotification($event, false);
                $lineMsg = "LINE送信: {$result['success_count']}件";
                if ($result['failure_count'] > 0) { $lineMsg .= "（{$result['failure_count']}件失敗）"; }
            }

            DB::commit();

            $successMsg = 'イベントを更新しました。' . ($lineMsg ? ' ' . $lineMsg : '');
            return redirect()->route('admin.events.show', $event->id)
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('イベント更新エラー', [
                'error'    => $e->getMessage(),
                'event_id' => $event->id,
                'user_id'  => $user->id,
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
        if ($user->role === 'year_admin' && $event->graduation_year != $user->graduation_year) {
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
        if ($user->role === 'year_admin' && $event->graduation_year != $user->graduation_year) {
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
            fputcsv($file, ['開催場所', $event->location]);
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
