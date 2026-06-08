<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /**
     * Get a setting value (cached), with a fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $value = static::allSettings()[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Return all settings as a key => value array (cached for the request).
     */
    public static function allSettings(): array
    {
        return Cache::remember('site_settings_all', 300, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('site_settings_all');
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('site_settings_all'));
        static::deleted(fn () => Cache::forget('site_settings_all'));
    }
}
