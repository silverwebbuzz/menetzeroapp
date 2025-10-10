<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\DocumentProcessingLog;
use App\Models\DocumentUsageTracking;
use App\Services\DEWABillParser;
use App\Services\OCRService;
use Illuminate\Support\Facades\Storage;

class DocumentProcessorService
{
    protected $ocrService;

    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Process document with OCR extraction
     */
    public function processDocument(DocumentUpload $document): void
    {
        try {
            // Update status to processing
            $document->update(['status' => 'processing']);

            // Log processing start
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Starting OCR processing',
                ['file_path' => $document->file_path],
                'processing'
            );

            // Get file path
            $filePath = Storage::disk('private')->path($document->file_path);

            // Extract data using appropriate service
            if ($document->source_type === 'electricity') {
                // Use DEWA bill parser for electricity bills
                $dewaParser = new DEWABillParser();
                $extractedData = $dewaParser->parseBill($filePath);
            } else {
                // Use OCR service for other document types
                $extractedData = $this->ocrService->extractData(
                    $filePath, 
                    $document->source_type, 
                    $document->id, 
                    $document->company->users()->first()->id ?? null, 
                    $document->company_id
                );
            }

            // Validate extracted data
            if ($document->source_type === 'electricity') {
                $dewaParser = new DEWABillParser();
                $validation = $dewaParser->validateExtractedData($extractedData);
            } else {
                $validation = $this->ocrService->validateExtractedData($extractedData, $document->source_type);
            }

            // Calculate confidence score
            if ($document->source_type === 'electricity') {
                $confidence = $extractedData['confidence'] ?? 90; // DEWA parser provides confidence
            } else {
                $confidence = $this->ocrService->getConfidenceScore($extractedData);
            }

            // Update document with extracted data
            $document->update([
                'extracted_data' => $extractedData,
                'processed_data' => $extractedData,
                'ocr_confidence' => $confidence,
                'ocr_processed_at' => now(),
                'status' => $validation['is_valid'] ? 'extracted' : 'failed',
                'ocr_error_message' => $validation['is_valid'] ? null : implode(', ', $validation['errors'])
            ]);

            // Log processing completion
            DocumentProcessingLog::log(
                $document->id,
                $validation['is_valid'] ? 'info' : 'warning',
                $validation['is_valid'] ? 'OCR processing completed successfully' : 'OCR processing completed with validation errors',
                [
                    'confidence' => $confidence,
                    'validation_errors' => $validation['errors'],
                    'warnings' => $validation['warnings']
                ],
                'processing'
            );

        } catch (\Exception $e) {
            // Handle processing errors
            $document->update([
                'status' => 'failed',
                'ocr_error_message' => $e->getMessage(),
                'ocr_attempts' => $document->ocr_attempts + 1
            ]);

            // Log error
            DocumentProcessingLog::log(
                $document->id,
                'error',
                'OCR processing failed: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'processing'
            );
        }
    }

    /**
     * Retry OCR processing for failed documents
     */
    public function retryProcessing(DocumentUpload $document): void
    {
        if ($document->status !== 'failed') {
            throw new \Exception('Document is not in failed status');
        }

        if ($document->ocr_attempts >= 3) {
            throw new \Exception('Maximum retry attempts reached');
        }

        $this->processDocument($document);
    }

    /**
     * Handle OCR failure with fallback options
     */
    public function handleOCRFailure(DocumentUpload $document, string $error): void
    {
        $document->update([
            'status' => 'failed',
            'ocr_error_message' => $error,
            'ocr_attempts' => $document->ocr_attempts + 1
        ]);

        // Log the failure
        DocumentProcessingLog::log(
            $document->id,
            'error',
            'OCR processing failed: ' . $error,
            ['attempt' => $document->ocr_attempts],
            'processing'
        );

        // If max attempts reached, provide manual entry option
        if ($document->ocr_attempts >= 3) {
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Maximum OCR attempts reached. Manual entry recommended.',
                [],
                'processing'
            );
        }
    }

    /**
     * Get processing statistics for a company
     */
    public function getProcessingStats(int $companyId): array
    {
        $total = DocumentUpload::where('company_id', $companyId)->count();
        $successful = DocumentUpload::where('company_id', $companyId)
            ->where('status', 'extracted')
            ->count();
        $failed = DocumentUpload::where('company_id', $companyId)
            ->where('status', 'failed')
            ->count();
        $pending = DocumentUpload::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get documents by status
     */
    public function getDocumentsByStatus(int $companyId, string $status): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentUpload::where('company_id', $companyId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up old failed documents
     */
    public function cleanupOldFailedDocuments(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $documents = DocumentUpload::where('status', 'failed')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($documents as $document) {
            try {
                // Delete file from storage
                if (Storage::disk('private')->exists($document->file_path)) {
                    Storage::disk('private')->delete($document->file_path);
                }

                // Delete document record
                $document->delete();
                $count++;

            } catch (\Exception $e) {
                // Log error but continue with other documents
                DocumentProcessingLog::log(
                    $document->id,
                    'error',
                    'Failed to cleanup document: ' . $e->getMessage(),
                    [],
                    'cleanup'
                );
            }
        }

        return $count;
    }

    /**
     * Get usage statistics for a company
     */
    public function getUsageStats(int $companyId, string $period = 'month'): array
    {
        return DocumentUsageTracking::getCompanyStats($companyId, $period);
    }

    /**
     * Check if company has exceeded OCR limits
     */
    public function hasExceededLimit(int $companyId, int $limit = 10): bool
    {
        return DocumentUsageTracking::hasExceededLimit($companyId, $limit);
    }

    /**
     * Get remaining OCR requests for company
     */
    public function getRemainingRequests(int $companyId, int $limit = 10): int
    {
        return DocumentUsageTracking::getRemainingRequests($companyId, $limit);
    }
}
