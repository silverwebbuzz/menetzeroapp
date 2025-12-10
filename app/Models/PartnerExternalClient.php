<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_company_id',
        'client_name',
        'contact_person',
        'email',
        'phone',
        'industry',
        'sector',
        'address',
        'city',
        'country',
        'status',
        'notes',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    /**
     * Get the partner company that owns this external client.
     */
    public function partnerCompany()
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    /**
     * Get locations for this external client.
     */
    public function locations()
    {
        return $this->hasMany(PartnerExternalClientLocation::class, 'partner_external_client_id');
    }

    /**
     * Get documents for this external client.
     */
    public function documents()
    {
        return $this->hasMany(PartnerExternalClientDocument::class, 'partner_external_client_id');
    }

    /**
     * Get reports for this external client.
     */
    public function reports()
    {
        return $this->hasMany(PartnerExternalClientReport::class, 'partner_external_client_id');
    }

    /**
     * Scope for active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

