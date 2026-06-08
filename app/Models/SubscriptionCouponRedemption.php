<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionCouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id',
        'company_id',
        'subscription_id',
        'transaction_id',
        'discount_applied',
        'currency',
        'redeemed_at',
    ];

    protected $casts = [
        'discount_applied' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function coupon()
    {
        return $this->belongsTo(SubscriptionCoupon::class, 'coupon_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
