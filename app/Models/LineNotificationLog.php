<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LineNotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'user_id',
        'notification_month',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 通知対象（News / Event）
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * 受信ユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 月が変わっているかをチェックし、必要に応じてリセット
     */
    private static function resetMonthIfNeeded(): void
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $lastStoredMonth = Setting::get('line_push_current_month', $currentMonth);

        // 月が異なる場合、新しい月として記録
        if ($lastStoredMonth !== $currentMonth) {
            Setting::set('line_push_current_month', $currentMonth);
            // 月が変わったことをログに記録
            \Illuminate\Support\Facades\Log::info('LINE プッシュ通知の月間カウント リセット', [
                'previous_month' => $lastStoredMonth,
                'new_month' => $currentMonth,
            ]);
        }
    }

    /**
     * 今月の送信済みカウント
     */
    public static function getThisMonthCount(): int
    {
        static::resetMonthIfNeeded();
        $currentMonth = Carbon::now()->format('Y-m');
        return static::where('notification_month', $currentMonth)->count();
    }

    /**
     * 月の残り送信可能数を計算
     */
    public static function getRemainingCount(): int
    {
        static::resetMonthIfNeeded();
        $limit = (int) Setting::get('line_push_limit', 200);
        $sentCount = static::getThisMonthCount();
        return max(0, $limit - $sentCount);
    }

    /**
     * 送信可能か判定
     */
    public static function canSend(): bool
    {
        return static::getRemainingCount() > 0;
    }
}
