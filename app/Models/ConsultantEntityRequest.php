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
    ];

    protected $casts = [
        'needs_sites_over_5' => 'boolean',
        'wants_enterprise' => 'boolean',
        'entity_count' => 'integer',
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
