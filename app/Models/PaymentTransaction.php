<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'client_payment_transactions';

    protected $fillable = [
        'company_id',
        'subscription_id',
        'billing_method_id',
        'transaction_type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'invoice_url',
        'invoice_number',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the company that owns this transaction.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription associated with this transaction.
     */
    public function subscription()
    {
        return $this->belongsTo(ClientSubscription::class, 'subscription_id');
    }

    /**
     * Get the billing method used for this transaction.
     */
    public function billingMethod()
    {
        return $this->belongsTo(ClientBillingMethod::class, 'billing_method_id');
    }

    /**
     * Scope for completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}

