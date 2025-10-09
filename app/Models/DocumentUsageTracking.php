<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentUsageTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'document_upload_id',
        'ocr_requests_count',
        'processing_time_ms',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
        'ocr_requests_count' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Get the company that owns this usage record
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who triggered this usage
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document upload that was processed
     */
    public function documentUpload(): BelongsTo
    {
        return $this->belongsTo(DocumentUpload::class);
    }

    /**
     * Scope query to filter by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope query to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope query to filter successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope query to filter failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Get usage statistics for a company
     */
    public static function getCompanyStats(int $companyId, string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        $query = self::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate);

        return [
            'total_requests' => $query->sum('ocr_requests_count'),
            'successful_requests' => $query->clone()->successful()->sum('ocr_requests_count'),
            'failed_requests' => $query->clone()->failed()->sum('ocr_requests_count'),
            'average_processing_time' => $query->clone()->whereNotNull('processing_time_ms')->avg('processing_time_ms'),
            'success_rate' => $query->clone()->count() > 0 ? 
                round(($query->clone()->successful()->count() / $query->clone()->count()) * 100, 2) : 0
        ];
    }

    /**
     * Get usage statistics for a user
     */
    public static function getUserStats(int $userId, string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        $query = self::where('user_id', $userId)
            ->where('created_at', '>=', $startDate);

        return [
            'total_requests' => $query->sum('ocr_requests_count'),
            'successful_requests' => $query->clone()->successful()->sum('ocr_requests_count'),
            'failed_requests' => $query->clone()->failed()->sum('ocr_requests_count'),
            'average_processing_time' => $query->clone()->whereNotNull('processing_time_ms')->avg('processing_time_ms'),
            'success_rate' => $query->clone()->count() > 0 ? 
                round(($query->clone()->successful()->count() / $query->clone()->count()) * 100, 2) : 0
        ];
    }

    /**
     * Check if company has exceeded OCR limits
     */
    public static function hasExceededLimit(int $companyId, int $limit = 10): bool
    {
        $monthlyUsage = self::where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ocr_requests_count');

        return $monthlyUsage >= $limit;
    }

    /**
     * Get remaining OCR requests for company
     */
    public static function getRemainingRequests(int $companyId, int $limit = 10): int
    {
        $monthlyUsage = self::where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ocr_requests_count');

        return max(0, $limit - $monthlyUsage);
    }
}
