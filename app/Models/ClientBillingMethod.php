<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBillingMethod extends Model
{
    use HasFactory;

    protected $table = 'client_billing_methods';

    protected $fillable = [
        'company_id',
        'payment_method_type',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'cardholder_name',
        'is_default',
        'is_active',
        'stripe_payment_method_id',
        'stripe_card_id',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'added_by',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the company that owns this billing method.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who added this billing method.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scope for active billing methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default billing method.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get formatted card display.
     */
    public function getCardDisplayAttribute()
    {
        if ($this->card_last4) {
            return $this->card_brand . ' •••• ' . $this->card_last4;
        }
        return 'N/A';
    }

    /**
     * Get formatted expiration date.
     */
    public function getExpirationDateAttribute()
    {
        if ($this->card_exp_month && $this->card_exp_year) {
            return str_pad($this->card_exp_month, 2, '0', STR_PAD_LEFT) . '/' . $this->card_exp_year;
        }
        return 'N/A';
    }
}

