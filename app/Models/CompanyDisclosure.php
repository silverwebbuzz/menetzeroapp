<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDisclosure extends Model
{
    protected $fillable = [
        'company_id', 'framework', 'section', 'fiscal_year',
        'content', 'status', 'last_edited_by',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'content' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }
}
