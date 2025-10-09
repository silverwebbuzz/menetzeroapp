<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'location_id',
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
        'measurement_id',
        'integration_status',
        'integration_attempts',
        'last_integration_attempt',
        'integration_error_message',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'processed_data' => 'array',
        'approved_data' => 'array',
        'ocr_confidence' => 'decimal:2',
        'ocr_processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'last_integration_attempt' => 'datetime',
        'file_size' => 'integer',
        'ocr_attempts' => 'integer',
        'integration_attempts' => 'integer',
    ];

    /**
     * Get the company that owns the document
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the location associated with the document
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who approved the document
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the measurement created from this document
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Get processing logs for this document
     */
    public function processingLogs(): HasMany
    {
        return $this->hasMany(DocumentProcessingLog::class);
    }

    /**
     * Get usage tracking records for this document
     */
    public function usageTracking(): HasMany
    {
        return $this->hasMany(DocumentUsageTracking::class);
    }

    /**
     * Scope query to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query to filter by source type
     */
    public function scopeBySourceType($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope query to filter by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file icon based on file type
     */
    public function getFileIconAttribute(): string
    {
        return match($this->file_type) {
            'pdf' => '📄',
            'jpg', 'jpeg', 'png' => '🖼️',
            default => '📁'
        };
    }

    /**
     * Get the status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'extracted' => 'green',
            'reviewed' => 'purple',
            'approved' => 'green',
            'rejected' => 'red',
            'integrated' => 'green',
            'failed' => 'red',
            default => 'gray'
        };
    }

    /**
     * Check if document can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['extracted', 'reviewed']);
    }

    /**
     * Check if document can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['extracted', 'reviewed']);
    }

    /**
     * Check if document is ready for integration
     */
    public function isReadyForIntegration(): bool
    {
        return $this->status === 'approved' && $this->integration_status === 'pending';
    }
}
