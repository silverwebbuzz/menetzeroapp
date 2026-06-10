<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSubscriptionAddon extends Model
{
    protected $fillable = [
        'partner_subscription_id',
        'addon_type',
        'quantity',
        'managed_company_id',
        'reporting_year',
        'amount_aed',
        'payment_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reporting_year' => 'integer',
        'amount_aed' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(PartnerSubscription::class, 'partner_subscription_id');
    }

    public function managedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'managed_company_id');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
