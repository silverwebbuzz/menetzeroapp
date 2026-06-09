<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantOrder extends Model
{
    protected $fillable = [
        'company_id',
        'consultant_id',
        'intro_request_id',
        'pack_type',
        'amount_aed',
        'commission_rate',
        'commission_aed',
        'payout_aed',
        'escrow_status',
        'order_status',
        'payment_reference',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_aed' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'commission_aed' => 'decimal:2',
            'payout_aed' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function introRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultantIntroRequest::class, 'intro_request_id');
    }
}
