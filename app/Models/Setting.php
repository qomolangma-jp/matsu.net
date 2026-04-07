<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'group'];

    const CACHE_KEY = 'app_settings';

    /**
     * キーで値を取得。見つからなければ $default を返す。
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::allCached();

        if (!isset($settings[$key])) {
            return $default;
        }

        $setting = $settings[$key];

        return match ($setting['type']) {
            'boolean' => (bool) $setting['value'],
            default   => $setting['value'],
        };
    }

    /**
     * キーに値をセットしてキャッシュを破棄
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * 複数キーを一括セット
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::where('key', $key)->update(['value' => $value, 'updated_at' => now()]);
        }
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * 全設定をキャッシュから取得（key => ['value', 'type', ...] の連想配列）
     */
    public static function allCached(): array
    {
        return Cache::remember(static::CACHE_KEY, 3600, function () {
            return static::all()->keyBy('key')->map(function ($s) {
                return ['value' => $s->value, 'type' => $s->type];
            })->toArray();
        });
    }

    /**
     * グループ別に全行を返す
     */
    public static function grouped(): array
    {
        return static::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->toArray();
    }
}
