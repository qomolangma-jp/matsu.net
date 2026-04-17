<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LineNotificationLog;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'deadline',
        'capacity',
        'graduation_year',
        'created_by',
        'is_published',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'deadline' => 'datetime',
        'capacity' => 'integer',
        'graduation_year' => 'integer',
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
     * LINE通知ログ
     */
    public function lineNotificationLogs()
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
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
        return $query->whereNull('graduation_year');
    }

    public function scopeByGraduationYear($query, $graduationYear)
    {
        return $query->where('graduation_year', $graduationYear);
    }

    public function scopeFilterByPermission($query, User $user)
    {
        if ($user->role === 'master_admin') {
            return $query;
        }

        if ($user->role === 'year_admin') {
            return $query->where(function($q) use ($user) {
                $q->whereNull('graduation_year')
                  ->orWhere('graduation_year', $user->graduation_year);
            });
        }

        return $query->where(function($q) use ($user) {
            $q->whereNull('graduation_year')
              ->orWhere('graduation_year', $user->graduation_year);
        })->published();
    }

    public function getAttendingCountAttribute()
    {
        return $this->attendances()->where('status', 'attending')->count();
    }

    public function getAbsentCountAttribute()
    {
        return $this->attendances()->where('status', 'absent')->count();
    }

    public function getPendingCountAttribute()
    {
        $targetUsers = User::approved();

        if ($this->graduation_year) {
            $targetUsers = $targetUsers->where('graduation_year', $this->graduation_year);
        }

        $totalTarget = $targetUsers->count();
        $responded = $this->attendances()->count();

        return max(0, $totalTarget - $responded);
    }

    public function getIsClosedAttribute()
    {
        return $this->deadline && now()->isAfter($this->deadline);
    }

    public function getIsFullAttribute()
    {
        return $this->capacity && $this->attending_count >= $this->capacity;
    }

    public function getTargetYearDisplayAttribute()
    {
        if (!$this->graduation_year) {
            return '全体同窓会';
        }

        return "{$this->graduation_year}年（" . ($this->graduation_year - 1947) . "回期）";
    }
}
