<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'display_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * 多対多リレーション: このカテゴリーに属するユーザー
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user')
            ->withTimestamps()
            ->withPivot('assigned_at', 'assigned_by', 'notes');
    }

    /**
     * スコープ: アクティブなカテゴリーのみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * スコープ: タイプで絞り込み
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * スコープ: 地区会のみ
     */
    public function scopeDistrict($query)
    {
        return $query->where('type', 'district');
    }

    /**
     * モデルイベント: slugの自動生成
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * カテゴリータイプのラベルを取得
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'district' => '地区会',
            'role' => '役職',
            'other' => 'その他',
            default => $this->type,
        };
    }
}
