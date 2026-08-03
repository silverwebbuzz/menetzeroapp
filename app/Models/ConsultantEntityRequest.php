<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantEntityRequest extends Model
{
    protected $fillable = [
        'consultant_company_id',
        'user_id',
        'entity_count',
        'needs_sites_over_5',
        'wants_enterprise',
        'message',
        'status',
        'admin_notes',
        'quote_amount_aed',
        'quote_breakdown',
        'quoted_at',
        'paid_at',
        'activated_at',
    ];

    protected $casts = [
        'needs_sites_over_5' => 'boolean',
        'wants_enterprise' => 'boolean',
        'entity_count' => 'integer',
        'quote_amount_aed' => 'float',
        'quoted_at' => 'datetime',
        'paid_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function consultantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'consultant_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
