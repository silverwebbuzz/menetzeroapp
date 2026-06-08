<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionCoupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'discount_percent',
        'discount_amount_aed',
        'discount_amount_inr',
        'subscription_plan_id',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount_aed' => 'decimal:2',
        'discount_amount_inr' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function redemptions()
    {
        return $this->hasMany(SubscriptionCouponRedemption::class, 'coupon_id');
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public static function generateCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isWithinWindow(): bool
    {
        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'free' => 'Free plan',
            'percent' => rtrim(rtrim((string) $this->discount_percent, '0'), '.') . '% off',
            'fixed' => 'Fixed discount',
            default => $this->type,
        };
    }
}
