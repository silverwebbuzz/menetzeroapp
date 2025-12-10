<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'original_name',
        'source_type',
        'document_category',
        'extracted_data',
        'processed_data',
        'ocr_confidence',
        'ocr_processed_at',
        'ocr_attempts',
        'ocr_error_message',
        'status',
        'approved_data',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'partner_external_client_measurement_id',
        'integration_status',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'processed_data' => 'array',
        'approved_data' => 'array',
        'ocr_confidence' => 'decimal:2',
        'ocr_processed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the external client that owns this document.
     */
    public function externalClient()
    {
        return $this->belongsTo(PartnerExternalClient::class, 'partner_external_client_id');
    }

    /**
     * Get the user who approved this document.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the measurement this document is integrated with.
     */
    public function measurement()
    {
        return $this->belongsTo(PartnerExternalClientMeasurement::class, 'partner_external_client_measurement_id');
    }

    /**
     * Scope for specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}

