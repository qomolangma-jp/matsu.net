<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'line_id',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'birth_date',
        'graduation_year',
        'email',
        'password',
        'phone',
        'postal_code',
        'address',
        'mail_unreachable',
        'role',
        'approval_status',
        'approved_at',
        'approved_by',
        'approval_note',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'mail_unreachable' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * フルネームを取得
     */
    public function getFullNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }

    /**
     * フルネーム（カナ）を取得
     */
    public function getFullNameKanaAttribute()
    {
        return $this->last_name_kana . ' ' . $this->first_name_kana;
    }

    /**
     * 回期を取得（卒業年度から計算）
     * 
     * 【計算基準】
     * - 高校51回期 = 1999年3月卒業（1998年度）
     * - 回期番号 = 卒業年度 - 1947
     */
    public function getGraduationPeriodAttribute()
    {
        return $this->graduation_year - 1947;
    }

    /**
     * 承認者リレーション
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 多対多リレーション: このユーザーが属するカテゴリー
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_user')
            ->withTimestamps()
            ->withPivot('assigned_at', 'assigned_by', 'notes');
    }

    /**
     * 地区会カテゴリーのみ取得
     */
    public function districtCategories()
    {
        return $this->categories()->where('type', 'district');
    }

    /**
     * カテゴリー名の配列を取得（表示用）
     */
    public function getCategoryNamesAttribute(): array
    {
        return $this->categories->pluck('name')->toArray();
    }

    // ==================== Query Scopes ====================

    /**
     * 卒業年度でフィルタ
     */
    public function scopeByGraduationYear($query, $year)
    {
        if ($year) {
            return $query->where('graduation_year', $year);
        }
        return $query;
    }

    /**
     * 地区会でフィルタ（カテゴリーIDまたは名前）
     */
    public function scopeByCategory($query, $category)
    {
        if ($category) {
            return $query->whereHas('categories', function($q) use ($category) {
                // カテゴリーIDまたは名前で検索
                if (is_numeric($category)) {
                    $q->where('categories.id', $category);
                } else {
                    $q->where('categories.name', $category);
                }
            });
        }
        return $query;
    }

    /**
     * カテゴリーIDでフィルタ（新しい推奨メソッド）
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    /**
     * 複数カテゴリーでフィルタ（OR条件）
     */
    public function scopeInCategories($query, array $categoryIds)
    {
        return $query->whereHas('categories', function($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }

    /**
     * 郵送物不達フラグでフィルタ
     */
    public function scopeByMailUnreachable($query, $unreachable)
    {
        if ($unreachable !== null && $unreachable !== '') {
            return $query->where('mail_unreachable', (bool)$unreachable);
        }
        return $query;
    }

    /**
     * 承認ステータスでフィルタ
     */
    public function scopeByApprovalStatus($query, $status)
    {
        if ($status) {
            return $query->where('approval_status', $status);
        }
        return $query;
    }

    /**
     * 権限でフィルタ
     */
    public function scopeByRole($query, $role)
    {
        if ($role) {
            return $query->where('role', $role);
        }
        return $query;
    }

    /**
     * 学年管理者の場合、自学年のみ表示
     */
    public function scopeFilterByPermission($query, $user)
    {
        if ($user->role === 'year_admin') {
            return $query->where('graduation_year', $user->graduation_year);
        }
        // マスター管理者は全件表示
        return $query;
    }

    /**
     * 検索（氏名・カナ）
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($q) use ($keyword) {
                $q->where('last_name', 'like', "%{$keyword}%")
                  ->orWhere('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name_kana', 'like', "%{$keyword}%")
                  ->orWhere('first_name_kana', 'like', "%{$keyword}%");
            });
        }
        return $query;
    }

    /**
     * 承認済みユーザーのみ
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * 承認待ちユーザーのみ
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }
}
