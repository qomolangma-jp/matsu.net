<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'guests_count',
        'remarks',
        'responded_at',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * イベントリレーション
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * ユーザーリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 出席
     */
    public function scopeAttending($query)
    {
        return $query->where('status', 'attending');
    }

    /**
     * 欠席
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * ステータス表示
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'attending' => '出席',
            'absent' => '欠席',
            default => '未定',
        };
    }

    /**
     * ステータスバッジクラス
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'attending' => 'bg-success',
            'absent' => 'bg-secondary',
            default => 'bg-warning',
        };
    }
}
