<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceRoster extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * CSV構造に対応（14列）
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'graduation_term',  // 卒業回（例: 高校51回期）
        'name',             // 氏名（例: 相河 奈美）
        'gender',           // 性別
        'status',           // 状態/会員区分
        'role_1',           // 役職1
        'role_2',           // 役職2
        'former_name',      // 旧姓
        'kana',             // フリガナ（半角カナ）
        'notes',            // 備考/更新履歴
        'postal_code',      // 郵便番号
        'address_1',        // 住所1
        'address_2',        // 住所2
        'address_3',        // 住所3
        'phone',            // 電話番号
        'is_registered',    // システム登録済みフラグ
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_registered' => 'boolean',
        ];
    }

    /**
     * 完全な住所を取得
     */
    public function getFullAddressAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->address_1,
            $this->address_2,
            $this->address_3,
        ])));
    }

    /**
     * 卒業年度を抽出（graduation_termから数値を取得）
     * 例: "高校51回期" → 1967 + 51 = 2018
     */
    public function getGraduationYearAttribute(): ?int
    {
        if (preg_match('/(\d+)回期/', $this->graduation_term, $matches)) {
            $term = (int) $matches[1];
            return 1967 + $term; // 基準年度は実際の規則に合わせて調整
        }
        return null;
    }

    /**
     * 役職を取得（role_1とrole_2の統合）
     */
    public function getRolesAttribute(): string
    {
        $roles = array_filter([$this->role_1, $this->role_2]);
        return implode(', ', $roles);
    }

    /**
     * スコープ: 卒業回で絞り込み
     */
    public function scopeByGraduationTerm($query, string $term)
    {
        return $query->where('graduation_term', 'LIKE', "%{$term}%");
    }

    /**
     * スコープ: 卒業年度で絞り込み（曖昧検索）
     */
    public function scopeByGraduationYear($query, int $year)
    {
        return $query->where('graduation_term', 'LIKE', "%{$year}%");
    }

    /**
     * スコープ: 氏名で絞り込み
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%");
    }

    /**
     * スコープ: 未登録者のみ
     */
    public function scopeNotRegistered($query)
    {
        return $query->where('is_registered', false);
    }

    /**
     * スコープ: 登録済みのみ
     */
    public function scopeRegistered($query)
    {
        return $query->where('is_registered', true);
    }
}
