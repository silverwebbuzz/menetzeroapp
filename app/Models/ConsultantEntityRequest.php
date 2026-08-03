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
        'package_code',
        'needs_sites_over_5',
        'extras',
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
        'extras' => 'array',
        'quote_amount_aed' => 'float',
        'quoted_at' => 'datetime',
        'paid_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function packageLabel(): string
    {
        if ($this->package_code) {
            return \App\Data\CompanyPackageOptions::label($this->package_code);
        }

        return $this->wants_enterprise ? 'Enterprise' : 'Standard';
    }

    public function consultantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'consultant_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
