<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed credentials for a payment gateway (Razorpay / Cashfree).
 */
class PaymentGateway extends Model
{
    protected $fillable = [
        'gateway',
        'label',
        'is_enabled',
        'mode',
        'key_id',
        'key_secret',
        'webhook_secret',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        // Secrets are stored encrypted at rest.
        'key_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];

    /**
     * Fetch the settings row for a gateway code.
     */
    public static function forGateway(string $gateway): ?self
    {
        return static::where('gateway', $gateway)->first();
    }

    /**
     * Enabled gateways that have both an id and secret configured, in order.
     */
    public static function enabled()
    {
        return static::where('is_enabled', true)
            ->whereNotNull('key_id')
            ->whereNotNull('key_secret')
            ->orderBy('sort_order')
            ->get();
    }

    /** Whether any gateway is configured and ready for checkout. */
    public static function checkoutAvailable(): bool
    {
        return static::enabled()->isNotEmpty();
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    public function isConfigured(): bool
    {
        return !empty($this->key_id) && !empty($this->key_secret);
    }
}
