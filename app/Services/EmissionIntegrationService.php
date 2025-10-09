<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\Measurement;
use App\Models\MeasurementData;
use App\Models\EmissionSourceMaster;
use App\Models\DocumentProcessingLog;
use Illuminate\Support\Facades\DB;

class EmissionIntegrationService
{
    /**
     * Integrate approved document with emission sources
     */
    public function integrateDocument(DocumentUpload $document): Measurement
    {
        try {
            DB::beginTransaction();

            // Create new measurement record
            $measurement = Measurement::create([
                'company_id' => $document->company_id,
                'location_id' => $document->location_id,
                'measurement_period' => now()->format('Y-m'),
                'status' => 'draft',
                'created_by' => $document->approved_by,
                'notes' => 'Created from document upload: ' . $document->original_name
            ]);

            // Map document data to emission source
            $emissionSourceId = $this->mapSourceTypeToEmissionSource($document->source_type);

            if (!$emissionSourceId) {
                throw new \Exception('No emission source found for type: ' . $document->source_type);
            }

            // Create measurement data records
            $this->createMeasurementData($measurement, $emissionSourceId, $document->approved_data, $document->approved_by);

            // Update document with measurement link
            $document->update([
                'measurement_id' => $measurement->id,
                'integration_status' => 'integrated'
            ]);

            // Log successful integration
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Document successfully integrated with measurement',
                [
                    'measurement_id' => $measurement->id,
                    'emission_source_id' => $emissionSourceId,
                    'data_fields' => array_keys($document->approved_data)
                ],
                'integration'
            );

            DB::commit();

            return $measurement;

        } catch (\Exception $e) {
            DB::rollBack();

            // Update document with integration failure
            $document->update([
                'integration_status' => 'failed',
                'integration_error_message' => $e->getMessage(),
                'integration_attempts' => $document->integration_attempts + 1,
                'last_integration_attempt' => now()
            ]);

            // Log integration failure
            DocumentProcessingLog::log(
                $document->id,
                'error',
                'Failed to integrate document: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'integration'
            );

            throw $e;
        }
    }

    /**
     * Map source type to emission source master
     */
    private function mapSourceTypeToEmissionSource(string $sourceType): ?int
    {
        $mapping = [
            'electricity' => 'Electricity Consumption',
            'fuel' => 'Fuel Consumption',
            'waste' => 'Waste Disposal',
            'water' => 'Water Consumption',
            'transport' => 'Transportation',
            'other' => 'Other Emissions'
        ];

        $sourceName = $mapping[$sourceType] ?? null;
        
        if (!$sourceName) {
            return null;
        }

        $emissionSource = EmissionSourceMaster::where('name', 'like', '%' . $sourceName . '%')
            ->where('is_active', true)
            ->first();

        return $emissionSource?->id;
    }

    /**
     * Create measurement data records
     */
    private function createMeasurementData(Measurement $measurement, int $emissionSourceId, array $approvedData, int $userId): void
    {
        foreach ($approvedData as $fieldName => $fieldValue) {
            // Skip non-numeric values for now
            if (!is_numeric($fieldValue)) {
                continue;
            }

            MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $emissionSourceId,
                'field_name' => $fieldName,
                'field_value' => (string) $fieldValue,
                'field_type' => 'number',
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
        }
    }

    /**
     * Batch integrate multiple documents
     */
    public function batchIntegrate(array $documentIds): array
    {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($documentIds as $documentId) {
            try {
                $document = DocumentUpload::findOrFail($documentId);
                
                if ($document->status !== 'approved') {
                    $results['failed'][] = [
                        'document_id' => $documentId,
                        'error' => 'Document is not approved'
                    ];
                    continue;
                }

                $measurement = $this->integrateDocument($document);
                $results['successful'][] = [
                    'document_id' => $documentId,
                    'measurement_id' => $measurement->id
                ];

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'document_id' => $documentId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get integration statistics
     */
    public function getIntegrationStats(int $companyId): array
    {
        $total = DocumentUpload::where('company_id', $companyId)
            ->where('status', 'approved')
            ->count();
            
        $integrated = DocumentUpload::where('company_id', $companyId)
            ->where('integration_status', 'integrated')
            ->count();
            
        $failed = DocumentUpload::where('company_id', $companyId)
            ->where('integration_status', 'failed')
            ->count();
            
        $pending = DocumentUpload::where('company_id', $companyId)
            ->where('integration_status', 'pending')
            ->count();

        return [
            'total_approved' => $total,
            'integrated' => $integrated,
            'failed' => $failed,
            'pending' => $pending,
            'integration_rate' => $total > 0 ? round(($integrated / $total) * 100, 2) : 0
        ];
    }

    /**
     * Retry failed integrations
     */
    public function retryFailedIntegrations(int $companyId): array
    {
        $failedDocuments = DocumentUpload::where('company_id', $companyId)
            ->where('integration_status', 'failed')
            ->where('status', 'approved')
            ->get();

        $results = [
            'successful' => 0,
            'failed' => 0
        ];

        foreach ($failedDocuments as $document) {
            try {
                $this->integrateDocument($document);
                $results['successful']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Validate integration data
     */
    public function validateIntegrationData(array $data, string $sourceType): array
    {
        $errors = [];
        $warnings = [];

        // Basic validation rules
        $requiredFields = $this->getRequiredFieldsForSourceType($sourceType);
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '{$field}' is required for {$sourceType}";
            }
        }

        // Value range validation
        foreach ($data as $field => $value) {
            if (is_numeric($value)) {
                $range = $this->getValueRangeForField($field, $sourceType);
                if ($range && ($value < $range['min'] || $value > $range['max'])) {
                    $warnings[] = "Field '{$field}' value {$value} is outside expected range ({$range['min']}-{$range['max']})";
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get required fields for source type
     */
    private function getRequiredFieldsForSourceType(string $sourceType): array
    {
        $requiredFields = [
            'electricity' => ['kwh', 'amount'],
            'fuel' => ['litres', 'price'],
            'waste' => ['tonnes'],
            'water' => ['cubic_meters'],
            'transport' => ['kilometers'],
            'other' => ['quantity', 'amount']
        ];

        return $requiredFields[$sourceType] ?? ['quantity', 'amount'];
    }

    /**
     * Get value range for field validation
     */
    private function getValueRangeForField(string $field, string $sourceType): ?array
    {
        $ranges = [
            'kwh' => ['min' => 0, 'max' => 10000],
            'litres' => ['min' => 0, 'max' => 500],
            'tonnes' => ['min' => 0, 'max' => 100],
            'cubic_meters' => ['min' => 0, 'max' => 2000],
            'kilometers' => ['min' => 0, 'max' => 2000],
            'amount' => ['min' => 0, 'max' => 10000],
            'price' => ['min' => 0, 'max' => 10000]
        ];

        return $ranges[$field] ?? null;
    }
}
