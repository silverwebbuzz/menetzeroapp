<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_upload_id',
        'log_level',
        'message',
        'context',
        'processing_step',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Get the document upload that owns this log
     */
    public function documentUpload(): BelongsTo
    {
        return $this->belongsTo(DocumentUpload::class);
    }

    /**
     * Scope query to filter by log level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('log_level', $level);
    }

    /**
     * Scope query to filter by processing step
     */
    public function scopeByStep($query, $step)
    {
        return $query->where('processing_step', $step);
    }

    /**
     * Create a log entry
     */
    public static function log($documentUploadId, $level, $message, $context = null, $step = null)
    {
        return self::create([
            'document_upload_id' => $documentUploadId,
            'log_level' => $level,
            'message' => $message,
            'context' => $context,
            'processing_step' => $step,
        ]);
    }
}
