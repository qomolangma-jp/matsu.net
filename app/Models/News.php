<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LineNotificationLog;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'target_graduation_years',
        'is_line_notification',
        'is_top_display',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'target_graduation_years' => 'array',
        'is_line_notification' => 'boolean',
        'is_top_display' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 作成者リレーション
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * LINE通知ログ
     */
    public function lineNotificationLogs()
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
    }

    /**
     * TOPページ表示用のニュースを取得
     */
    public function scopeTopDisplay($query)
    {
        return $query->where('is_top_display', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now())
                     ->orderBy('published_at', 'desc');
    }

    /**
     * 公開済みニュース
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * 特定の卒業年度向けニュース
     */
    public function scopeForGraduationYear($query, $graduationYear)
    {
        return $query->where(function($q) use ($graduationYear) {
            $q->whereNull('target_graduation_years')
              ->orWhereJsonContains('target_graduation_years', $graduationYear);
        });
    }

    /**
     * 対象卒業年度の表示文字列
     */
    public function getTargetYearsDisplayAttribute()
    {
        if (empty($this->target_graduation_years)) {
            return '全学年';
        }

        $years = collect($this->target_graduation_years)
            ->sort()
            ->map(fn($year) => "{$year}年")
            ->join('、');

        return $years;
    }
}
