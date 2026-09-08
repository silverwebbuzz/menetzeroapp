<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed credentials for a payment gateway (Razorpay).
 *
 * Test and live credentials are stored side by side, and `mode` selects which
 * pair is in use. `key_id`, `key_secret` and `webhook_secret` are accessors
 * over the active pair rather than columns, so callers -- PaymentService, the
 * checkout views, PaymentWebhookController -- read them exactly as before and
 * automatically follow the mode switch.
 */
class PaymentGateway extends Model
{
    protected $fillable = [
        'gateway',
        'label',
        'is_enabled',
        'mode',
        'test_key_id',
        'test_key_secret',
        'test_webhook_secret',
        'live_key_id',
        'live_key_secret',
        'live_webhook_secret',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        // Secrets are stored encrypted at rest. Key ids are not secret -- they
        // are handed to the browser to open checkout -- so they stay plain.
        'test_key_secret' => 'encrypted',
        'test_webhook_secret' => 'encrypted',
        'live_key_secret' => 'encrypted',
        'live_webhook_secret' => 'encrypted',
    ];

    /**
     * Credentials for the mode this gateway is currently running in.
     *
     * Declared as accessors so `$gateway->key_id` keeps meaning "the key id to
     * use right now" everywhere it is already read.
     */
    protected function keyId(): Attribute
    {
        return Attribute::get(fn () => $this->credential('key_id'));
    }

    protected function keySecret(): Attribute
    {
        return Attribute::get(fn () => $this->credential('key_secret'));
    }

    protected function webhookSecret(): Attribute
    {
        return Attribute::get(fn () => $this->credential('webhook_secret'));
    }

    /** Read one credential from the active mode's column. */
    protected function credential(string $name): ?string
    {
        return $this->{$this->modePrefix() . '_' . $name};
    }

    /** 'live' or 'test' — anything unrecognised is treated as test. */
    public function modePrefix(): string
    {
        return $this->mode === 'live' ? 'live' : 'test';
    }

    /**
     * Fetch the settings row for a gateway code.
     */
    public static function forGateway(string $gateway): ?self
    {
        return static::where('gateway', $gateway)->first();
    }

    /**
     * Enabled gateways whose ACTIVE mode has both an id and a secret.
     *
     * Filtered in PHP rather than SQL: which columns matter depends on each
     * row's mode, so a WHERE clause would have to branch per row. The table
     * holds a handful of rows, so the cost is irrelevant.
     */
    public static function enabled()
    {
        return static::where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (self $gateway) => $gateway->isConfigured())
            ->values();
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

    /** Is the ACTIVE mode usable? Credentials for the other mode do not count. */
    public function isConfigured(): bool
    {
        return !empty($this->key_id) && !empty($this->key_secret);
    }

    /** Is the given mode ready, so the UI can show which sides are filled in? */
    public function hasCredentialsFor(string $mode): bool
    {
        $prefix = $mode === 'live' ? 'live' : 'test';

        return !empty($this->{$prefix . '_key_id'}) && !empty($this->{$prefix . '_key_secret'});
    }
}
