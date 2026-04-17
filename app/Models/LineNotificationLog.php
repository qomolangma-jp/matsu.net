<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineNotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'user_id',
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
}
