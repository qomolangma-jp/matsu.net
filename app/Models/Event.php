<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'event_location',
        'registration_deadline',
        'max_participants',
        'target_graduation_year',
        'created_by',
        'is_published',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'max_participants' => 'integer',
        'target_graduation_year' => 'integer',
        'is_published' => 'boolean',
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
     * 出欠リレーション
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * 出席者
     */
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'attendances')
                    ->wherePivot('status', 'attending')
                    ->withPivot(['note', 'created_at']);
    }

    /**
     * 公開済みイベント
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * 全体イベント（卒業年度指定なし）
     */
    public function scopeAllYears($query)
    {
        return $query->whereNull('target_graduation_year');
    }

    /**
     * 特定学年のイベント
     */
    public function scopeByGraduationYear($query, $graduationYear)
    {
        return $query->where('target_graduation_year', $graduationYear);
    }

    /**
     * 権限に応じたイベント取得
     */
    public function scopeFilterByPermission($query, User $user)
    {
        if ($user->role === 'master_admin') {
            return $query;
        }

        if ($user->role === 'year_admin') {
            return $query->where(function($q) use ($user) {
                $q->whereNull('target_graduation_year')
                  ->orWhere('target_graduation_year', $user->graduation_year);
            });
        }

        // 一般ユーザーの場合
        return $query->where(function($q) use ($user) {
            $q->whereNull('target_graduation_year')
              ->orWhere('target_graduation_year', $user->graduation_year);
        })->published();
    }

    /**
     * 出席者数
     */
    public function getAttendingCountAttribute()
    {
        return $this->attendances()->where('status', 'attending')->count();
    }

    /**
     * 欠席者数
     */
    public function getAbsentCountAttribute()
    {
        return $this->attendances()->where('status', 'absent')->count();
    }

    /**
     * 未回答者数（対象ユーザー - 回答済み）
     */
    public function getPendingCountAttribute()
    {
        $targetUsers = User::approved();

        if ($this->target_graduation_year) {
            $targetUsers = $targetUsers->where('graduation_year', $this->target_graduation_year);
        }

        $totalTarget = $targetUsers->count();
        $responded = $this->attendances()->count();

        return max(0, $totalTarget - $responded);
    }

    /**
     * 募集締切チェック
     */
    public function getIsClosedAttribute()
    {
        return $this->registration_deadline && now()->isAfter($this->registration_deadline);
    }

    /**
     * 定員チェック
     */
    public function getIsFullAttribute()
    {
        return $this->max_participants && $this->attending_count >= $this->max_participants;
    }

    /**
     * 対象学年表示
     */
    public function getTargetYearDisplayAttribute()
    {
        if (!$this->target_graduation_year) {
            return '全体同窓会';
        }

        return "{$this->target_graduation_year}年（" . ($this->target_graduation_year - 1947) . "回期）";
    }
}
