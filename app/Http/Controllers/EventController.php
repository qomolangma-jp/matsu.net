<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    private function redirectGuestToLiff(Request $request)
    {
        return redirect('/?liff.state=' . urlencode($request->getPathInfo()));
    }

    /**
     * イベント一覧（ログインユーザーの卒業年度でフィルタ）
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user = $user->fresh();
        }

        if (!$user) {
            return $this->redirectGuestToLiff($request);
        }

        $query = Event::published()
            ->where(function ($q) use ($user) {
                $q->whereNull('graduation_year')
                  ->orWhere('graduation_year', $user->graduation_year);
            });

        // 期間フィルタ
        $filter = $request->input('filter', 'upcoming');
        if ($filter === 'past') {
            $query->where('event_date', '<', now())->orderByDesc('event_date');
        } else {
            $query->where('event_date', '>=', now())->orderBy('event_date');
        }

        $events = $query->paginate(15)->withQueryString();

        // 自分の回答を event_id をキーにしたコレクションで取得
        $myAttendances = Attendance::where('user_id', $user->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->get()
            ->keyBy('event_id');

        return view('events.index', compact('events', 'user', 'filter', 'myAttendances'));
    }

    /**
     * イベント詳細
     */
    public function show(Request $request, Event $event)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user = $user->fresh();
        }

        if (!$user) {
            return $this->redirectGuestToLiff($request);
        }

        // 未公開は404
        if (!$event->is_published) {
            abort(404);
        }

        // 対象卒業年度チェック
        if ($event->graduation_year && $event->graduation_year != $user->graduation_year) {
            abort(403, 'このイベントは対象外です。');
        }

        $attendance = Attendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        return view('events.show', compact('event', 'user', 'attendance'));
    }

    /**
     * 参加回答（登録 or 更新）
     */
    public function respond(Request $request, Event $event)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user = $user->fresh();
        }

        if (!$user) {
            return $this->redirectGuestToLiff($request);
        }

        if (!$event->is_published) {
            abort(404);
        }

        if ($event->graduation_year && $event->graduation_year != $user->graduation_year) {
            abort(403);
        }

        $validated = $request->validate([
            'status'       => ['required', 'in:attending,absent,pending'],
            'guests_count' => ['integer', 'min:0', 'max:10'],
            'remarks'      => ['nullable', 'string', 'max:500'],
        ]);

        Attendance::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status'       => $validated['status'],
                'guests_count' => $validated['guests_count'] ?? 0,
                'remarks'      => $validated['remarks'] ?? null,
                'responded_at' => now(),
            ]
        );

        return redirect()->route('events.show', $event)
            ->with('success', '参加回答を登録しました。');
    }
}
