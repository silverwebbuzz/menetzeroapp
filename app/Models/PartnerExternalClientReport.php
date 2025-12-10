<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_id',
        'report_type',
        'period_start',
        'period_end',
        'file_path',
        'generated_at',
        'generated_by',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the external client that owns this report.
     */
    public function externalClient()
    {
        return $this->belongsTo(PartnerExternalClient::class, 'partner_external_client_id');
    }

    /**
     * Get the user who generated this report.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

