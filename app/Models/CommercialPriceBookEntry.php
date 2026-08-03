<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CommercialPriceBookEntry extends Model
{
    public const CATEGORY_COMPANY = 'company_package';
    public const CATEGORY_CONSULTANT = 'consultant_rate';
    public const CATEGORY_EXTRA = 'extra';

    protected $fillable = [
        'code',
        'category',
        'label',
        'amount_aed',
        'is_custom',
        'notes',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'amount_aed' => 'float',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('commercial_price_book_all'));
        static::deleted(fn () => Cache::forget('commercial_price_book_all'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function allCached()
    {
        return Cache::remember('commercial_price_book_all', 300, function () {
            return static::query()->orderBy('category')->orderBy('sort_order')->get();
        });
    }

    public static function findByCode(string $code): ?self
    {
        return static::allCached()->firstWhere('code', $code);
    }
}
