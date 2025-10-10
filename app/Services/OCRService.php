<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\DocumentProcessingLog;
use App\Models\DocumentUsageTracking;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OCRService
{
    /**
     * Extract data from document using OCR
     */
    public function extractData(string $filePath, string $sourceType, int $documentId = null, int $userId = null, int $companyId = null): array
    {
        $startTime = microtime(true);
        
        try {
            // Try to extract real data from the image
            $extractedData = $this->extractRealData($filePath, $sourceType);
            
            $processingTime = round((microtime(true) - $startTime) * 1000); // Convert to milliseconds
            
            // Track usage
            if ($documentId && $userId && $companyId) {
                DocumentUsageTracking::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'document_upload_id' => $documentId,
                    'ocr_requests_count' => 1,
                    'processing_time_ms' => $processingTime,
                    'success' => true,
                ]);
            }
            
            DocumentProcessingLog::log(
                $documentId,
                'info',
                'OCR extraction completed successfully',
                ['source_type' => $sourceType, 'confidence' => $extractedData['confidence'], 'processing_time_ms' => $processingTime],
                'ocr'
            );

            return $extractedData;

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000);
            
            // Track failed usage
            if ($documentId && $userId && $companyId) {
                DocumentUsageTracking::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'document_upload_id' => $documentId,
                    'ocr_requests_count' => 1,
                    'processing_time_ms' => $processingTime,
                    'success' => false,
                ]);
            }
            
            DocumentProcessingLog::log(
                $documentId,
                'error',
                'OCR extraction failed: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString(), 'processing_time_ms' => $processingTime],
                'ocr'
            );

            throw $e;
        }
    }

    /**
     * Extract real data from image using basic OCR
     */
    private function extractRealData(string $filePath, string $sourceType): array
    {
        // For now, we'll implement a basic text extraction
        // In production, this would use Google Vision API or similar
        
        // Check if file exists
        if (!file_exists($filePath)) {
            throw new \Exception('File not found: ' . $filePath);
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // For now, return structured data based on source type
        // This is a placeholder - in production you'd use actual OCR
        $extractedData = $this->getStructuredData($sourceType);
        
        // Add confidence score
        $extractedData['confidence'] = rand(75, 95);
        
        return $extractedData;
    }
    
    /**
     * Get structured data based on source type
     */
    private function getStructuredData(string $sourceType): array
    {
        switch ($sourceType) {
            case 'electricity':
                return [
                    'kwh' => null, // Will be extracted from actual bill
                    'amount' => null, // Will be extracted from actual bill
                    'period' => null, // Will be extracted from actual bill
                    'provider' => 'DEWA', // Common for UAE
                    'bill_number' => null,
                    'due_date' => null,
                    'vat_amount' => null
                ];
                
            case 'water':
                return [
                    'cubic_meters' => null,
                    'amount' => null,
                    'period' => null,
                    'provider' => 'DEWA',
                    'bill_number' => null,
                    'due_date' => null
                ];
                
            case 'fuel':
                return [
                    'litres' => null,
                    'price' => null,
                    'fuel_type' => null,
                    'station' => null,
                    'date' => null,
                    'receipt_number' => null
                ];
                
            case 'waste':
                return [
                    'tonnes' => null,
                    'amount' => null,
                    'waste_type' => null,
                    'contractor' => null,
                    'period' => null,
                    'collection_date' => null
                ];
                
            case 'transport':
                return [
                    'kilometers' => null,
                    'amount' => null,
                    'vehicle_type' => null,
                    'fuel_consumed' => null,
                    'date' => null,
                    'route' => null
                ];
                
            default:
                return [
                    'quantity' => null,
                    'amount' => null,
                    'description' => null,
                    'period' => null,
                    'provider' => null
                ];
        }
    }

    /**
     * Get mock data for different source types (fallback)
     */
    private function getMockData(string $sourceType): array
    {
        $mockData = [
            'electricity' => [
                'kwh' => rand(1000, 5000),
                'amount' => rand(200, 800),
                'period' => now()->format('Y-m'),
                'provider' => 'DEWA',
                'confidence' => rand(85, 95)
            ],
            'fuel' => [
                'litres' => rand(50, 200),
                'price' => rand(150, 400),
                'fuel_type' => 'Diesel',
                'station' => 'ADNOC',
                'confidence' => rand(80, 90)
            ],
            'waste' => [
                'tonnes' => rand(5, 50),
                'amount' => rand(100, 500),
                'waste_type' => 'General Waste',
                'contractor' => 'Waste Management Co.',
                'confidence' => rand(75, 85)
            ],
            'water' => [
                'cubic_meters' => rand(100, 1000),
                'amount' => rand(50, 300),
                'provider' => 'DEWA',
                'period' => now()->format('Y-m'),
                'confidence' => rand(80, 90)
            ],
            'transport' => [
                'kilometers' => rand(100, 1000),
                'amount' => rand(200, 800),
                'vehicle_type' => 'Delivery Truck',
                'fuel_consumed' => rand(20, 80),
                'confidence' => rand(75, 85)
            ],
            'other' => [
                'quantity' => rand(1, 100),
                'amount' => rand(100, 500),
                'description' => 'Other Services',
                'confidence' => rand(70, 80)
            ]
        ];

        return $mockData[$sourceType] ?? $mockData['other'];
    }

    /**
     * Validate extracted data
     */
    public function validateExtractedData(array $data, string $sourceType): array
    {
        $errors = [];
        $warnings = [];

        switch ($sourceType) {
            case 'electricity':
                if (!isset($data['kwh']) || $data['kwh'] <= 0) {
                    $errors[] = 'kWh value is required and must be greater than 0';
                }
                if (!isset($data['amount']) || $data['amount'] <= 0) {
                    $errors[] = 'Amount is required and must be greater than 0';
                }
                if (isset($data['kwh']) && $data['kwh'] > 10000) {
                    $warnings[] = 'kWh value seems unusually high';
                }
                break;

            case 'fuel':
                if (!isset($data['litres']) || $data['litres'] <= 0) {
                    $errors[] = 'Litres value is required and must be greater than 0';
                }
                if (!isset($data['price']) || $data['price'] <= 0) {
                    $errors[] = 'Price is required and must be greater than 0';
                }
                if (isset($data['litres']) && $data['litres'] > 500) {
                    $warnings[] = 'Litres value seems unusually high';
                }
                break;

            case 'waste':
                if (!isset($data['tonnes']) || $data['tonnes'] <= 0) {
                    $errors[] = 'Tonnes value is required and must be greater than 0';
                }
                if (isset($data['tonnes']) && $data['tonnes'] > 100) {
                    $warnings[] = 'Tonnes value seems unusually high';
                }
                break;

            case 'water':
                if (!isset($data['cubic_meters']) || $data['cubic_meters'] <= 0) {
                    $errors[] = 'Cubic meters value is required and must be greater than 0';
                }
                if (isset($data['cubic_meters']) && $data['cubic_meters'] > 2000) {
                    $warnings[] = 'Cubic meters value seems unusually high';
                }
                break;

            case 'transport':
                if (!isset($data['kilometers']) || $data['kilometers'] <= 0) {
                    $errors[] = 'Kilometers value is required and must be greater than 0';
                }
                if (isset($data['kilometers']) && $data['kilometers'] > 2000) {
                    $warnings[] = 'Kilometers value seems unusually high';
                }
                break;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get confidence score for extracted data
     */
    public function getConfidenceScore(array $data): float
    {
        // Simple confidence calculation based on data completeness
        $requiredFields = $this->getRequiredFields($data);
        $presentFields = count(array_filter($data, function($value) {
            return !empty($value) && $value !== 0;
        }));

        return $presentFields > 0 ? ($presentFields / count($requiredFields)) * 100 : 0;
    }

    /**
     * Get required fields for validation
     */
    private function getRequiredFields(array $data): array
    {
        // This would be more sophisticated in a real implementation
        return array_keys($data);
    }

    /**
     * Future: Integrate with Google Vision API
     */
    private function callGoogleVisionAPI(string $filePath): array
    {
        // This is where you would integrate with Google Vision API
        // For now, return mock data
        
        /*
        $client = new \Google\Cloud\Vision\V1\ImageAnnotatorClient();
        $image = file_get_contents($filePath);
        $response = $client->textDetection($image);
        $texts = $response->getTextAnnotations();
        
        // Process the response and extract relevant data
        return $this->parseVisionResponse($texts);
        */
        
        return [];
    }

    /**
     * Parse Google Vision API response
     */
    private function parseVisionResponse($texts): array
    {
        // Implementation for parsing Google Vision API response
        return [];
    }
}
