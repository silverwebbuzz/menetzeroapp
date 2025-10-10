<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DEWABillParser
{
    /**
     * Parse DEWA bill PDF and extract structured data
     */
    public function parseBill(string $filePath): array
    {
        try {
            // For now, we'll create a comprehensive parser structure
            // In production, this would use actual PDF text extraction
            
            $extractedData = $this->extractBillData($filePath);
            
            // Process and structure the data
            $structuredData = $this->structureBillData($extractedData);
            
            return $structuredData;
            
        } catch (\Exception $e) {
            Log::error('DEWA Bill parsing failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Extract raw data from PDF using actual text extraction
     */
    private function extractBillData(string $filePath): array
    {
        // Extract text from PDF using simple text extraction
        $extractedText = $this->extractTextFromPDF($filePath);
        
        // Parse the extracted text to find bill data
        $billData = $this->parseBillText($extractedText);
        
        return $billData;
    }
    
    /**
     * Extract text from PDF file
     */
    private function extractTextFromPDF(string $filePath): string
    {
        try {
            // Use pdftotext command if available
            if (function_exists('shell_exec') && $this->commandExists('pdftotext')) {
                $output = shell_exec("pdftotext -layout \"$filePath\" -");
                return $output ?: '';
            }
            
            // Fallback: try to read PDF as text (basic approach)
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \Exception('Could not read PDF file');
            }
            
            // Simple text extraction (this is basic and may not work for all PDFs)
            $text = $this->basicPDFTextExtraction($content);
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error('PDF text extraction failed: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Check if a command exists
     */
    private function commandExists(string $command): bool
    {
        $return = shell_exec("which $command");
        return !empty($return);
    }
    
    /**
     * Basic PDF text extraction (fallback method)
     */
    private function basicPDFTextExtraction(string $content): string
    {
        // This is a very basic approach - in production you'd use a proper PDF library
        // For now, we'll try to extract readable text patterns
        
        $text = '';
        
        // Look for text between BT and ET markers (PDF text objects)
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Extract text from Tj and TJ operators
                preg_match_all('/\((.*?)\)\s*Tj/', $match, $textMatches);
                if (!empty($textMatches[1])) {
                    $text .= implode(' ', $textMatches[1]) . ' ';
                }
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Parse extracted text to find bill data
     */
    private function parseBillText(string $text): array
    {
        $billData = [
            // Initialize with null values
            'bill_number' => null,
            'issue_date' => null,
            'due_date' => null,
            'period_start' => null,
            'period_end' => null,
            'account_number' => null,
            'customer_name' => null,
            'premise_address' => null,
            'premise_type' => null,
            'electricity_consumption_kwh' => null,
            'electricity_charges_aed' => null,
            'water_consumption_cubic_meters' => null,
            'water_charges_aed' => null,
            'other_services_aed' => null,
            'dewa_total_aed' => null,
            'municipality_housing_aed' => null,
            'municipality_sewerage_aed' => null,
            'municipality_total_aed' => null,
            'additional_charges_aed' => null,
            'current_month_total_aed' => null,
            'previous_balance_aed' => null,
            'payments_received_aed' => null,
            'total_due_aed' => null,
            'vat_amount_aed' => null,
            'carbon_footprint_kg_co2e' => null,
            'provider' => 'DEWA',
            'provider_vat_number' => null,
            'service_areas' => ['Dubai'],
            'billing_period_days' => null,
            'consumption_slab' => null,
            'confidence' => 60, // Lower confidence for text extraction
            'extraction_method' => 'pdf_text_extraction',
            'bill_type' => 'DEWA_UTILITY_BILL',
            'raw_text' => $text // Store raw text for debugging
        ];
        
        // Extract bill number (look for patterns like 756595961)
        if (preg_match('/\b(\d{9,12})\b/', $text, $matches)) {
            $billData['bill_number'] = $matches[1];
        }
        
        // Extract dates (look for date patterns)
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $text, $matches)) {
            $billData['issue_date'] = $matches[1];
        }
        
        // Extract amounts (look for AED amounts)
        if (preg_match_all('/(\d+\.?\d*)\s*AED/', $text, $matches)) {
            $amounts = array_map('floatval', $matches[1]);
            if (!empty($amounts)) {
                $billData['total_due_aed'] = max($amounts); // Assume largest amount is total
            }
        }
        
        // Extract electricity consumption (look for kWh patterns)
        if (preg_match('/(\d+\.?\d*)\s*kWh/', $text, $matches)) {
            $billData['electricity_consumption_kwh'] = floatval($matches[1]);
        }
        
        // Extract water consumption (look for cubic meters)
        if (preg_match('/(\d+\.?\d*)\s*cubic\s*meters?/', $text, $matches)) {
            $billData['water_consumption_cubic_meters'] = floatval($matches[1]);
        }
        
        // Extract account number (look for long numbers)
        if (preg_match('/Account[:\s]*(\d{8,12})/', $text, $matches)) {
            $billData['account_number'] = $matches[1];
        }
        
        // Extract customer name (look for name patterns)
        if (preg_match('/Customer[:\s]*([A-Z\s]+)/', $text, $matches)) {
            $billData['customer_name'] = trim($matches[1]);
        }
        
        return $billData;
    }
    
    /**
     * Structure the extracted data for our system
     */
    private function structureBillData(array $rawData): array
    {
        return [
            // Bill Information
            'bill_information' => [
                'bill_number' => $rawData['bill_number'] ?? null,
                'issue_date' => $rawData['issue_date'] ?? null,
                'due_date' => $rawData['due_date'] ?? null,
                'period_start' => $rawData['period_start'] ?? null,
                'period_end' => $rawData['period_end'] ?? null,
                'account_number' => $rawData['account_number'] ?? null,
                'customer_name' => $rawData['customer_name'] ?? null,
                'premise_address' => $rawData['premise_address'] ?? null,
                'premise_type' => $rawData['premise_type'] ?? null
            ],
            
            // DEWA Services
            'dewa_services' => [
                'electricity_consumption_kwh' => $rawData['electricity_consumption_kwh'] ?? null,
                'electricity_charges_aed' => $rawData['electricity_charges_aed'] ?? null,
                'water_consumption_cubic_meters' => $rawData['water_consumption_cubic_meters'] ?? null,
                'water_charges_aed' => $rawData['water_charges_aed'] ?? null,
                'other_services_aed' => $rawData['other_services_aed'] ?? null,
                'dewa_total_aed' => $rawData['dewa_total_aed'] ?? null
            ],
            
            // Municipality Services
            'municipality_services' => [
                'housing_aed' => $rawData['municipality_housing_aed'] ?? null,
                'sewerage_aed' => $rawData['municipality_sewerage_aed'] ?? null,
                'total_aed' => $rawData['municipality_total_aed'] ?? null
            ],
            
            // Financial Summary
            'financial_summary' => [
                'additional_charges_aed' => $rawData['additional_charges_aed'] ?? null,
                'current_month_total_aed' => $rawData['current_month_total_aed'] ?? null,
                'previous_balance_aed' => $rawData['previous_balance_aed'] ?? null,
                'payments_received_aed' => $rawData['payments_received_aed'] ?? null,
                'total_due_aed' => $rawData['total_due_aed'] ?? null,
                'vat_amount_aed' => $rawData['vat_amount_aed'] ?? null
            ],
            
            // Energy Consumption (for carbon calculation)
            'energy_consumption' => [
                'total_electricity_kwh' => $rawData['electricity_consumption_kwh'] ?? null,
                'total_water_cubic_meters' => $rawData['water_consumption_cubic_meters'] ?? null
            ],
            
            // Carbon Footprint (if available)
            'carbon_footprint' => [
                'total_kg_co2e' => $rawData['carbon_footprint_kg_co2e'] ?? null,
                'breakdown' => $rawData['carbon_footprint_breakdown'] ?? null
            ],
            
            // Provider Information
            'provider_info' => [
                'provider' => $rawData['provider'] ?? 'DEWA',
                'vat_number' => $rawData['provider_vat_number'] ?? null,
                'service_areas' => $rawData['service_areas'] ?? ['Dubai']
            ],
            
            // Processing Information
            'processing_info' => [
                'confidence' => $rawData['confidence'] ?? 60,
                'extraction_method' => $rawData['extraction_method'] ?? 'pdf_text_extraction',
                'bill_type' => $rawData['bill_type'] ?? 'DEWA_UTILITY_BILL',
                'billing_period_days' => $rawData['billing_period_days'] ?? null,
                'consumption_slab' => $rawData['consumption_slab'] ?? null
            ],
            
            // Raw extracted text for debugging
            'raw_text' => $rawData['raw_text'] ?? null
        ];
    }
    
    /**
     * Get field mapping options for user selection
     */
    public function getFieldMappingOptions(): array
    {
        return [
            'electricity_consumption_kwh' => [
                'label' => 'Electricity Consumption (kWh)',
                'description' => 'Total electricity consumption in kilowatt-hours',
                'required' => true,
                'carbon_relevant' => true,
                'mapping_options' => [
                    'electricity_consumption_kwh' => 'Electricity Consumption (kWh)',
                    'total_electricity_kwh' => 'Total Electricity (kWh)',
                    'kwh_consumption' => 'kWh Consumption',
                    'electricity_usage' => 'Electricity Usage'
                ]
            ],
            'electricity_charges_aed' => [
                'label' => 'Electricity Charges (AED)',
                'description' => 'Electricity charges in UAE Dirhams',
                'required' => false,
                'carbon_relevant' => false,
                'mapping_options' => [
                    'electricity_charges_aed' => 'Electricity Charges (AED)',
                    'electricity_amount' => 'Electricity Amount',
                    'electricity_cost' => 'Electricity Cost',
                    'electricity_bill' => 'Electricity Bill'
                ]
            ],
            'water_consumption_cubic_meters' => [
                'label' => 'Water Consumption (Cubic Meters)',
                'description' => 'Total water consumption in cubic meters',
                'required' => false,
                'carbon_relevant' => true,
                'mapping_options' => [
                    'water_consumption_cubic_meters' => 'Water Consumption (Cubic Meters)',
                    'water_usage' => 'Water Usage',
                    'water_consumption' => 'Water Consumption',
                    'cubic_meters' => 'Cubic Meters'
                ]
            ],
            'water_charges_aed' => [
                'label' => 'Water Charges (AED)',
                'description' => 'Water charges in UAE Dirhams',
                'required' => false,
                'carbon_relevant' => false,
                'mapping_options' => [
                    'water_charges_aed' => 'Water Charges (AED)',
                    'water_amount' => 'Water Amount',
                    'water_cost' => 'Water Cost',
                    'water_bill' => 'Water Bill'
                ]
            ],
            'total_due_aed' => [
                'label' => 'Total Due (AED)',
                'description' => 'Total amount due for the billing period',
                'required' => false,
                'carbon_relevant' => false,
                'mapping_options' => [
                    'total_due_aed' => 'Total Due (AED)',
                    'total_amount' => 'Total Amount',
                    'bill_total' => 'Bill Total',
                    'amount_due' => 'Amount Due'
                ]
            ],
            'vat_amount_aed' => [
                'label' => 'VAT Amount (AED)',
                'description' => 'Value Added Tax amount',
                'required' => false,
                'carbon_relevant' => false,
                'mapping_options' => [
                    'vat_amount_aed' => 'VAT Amount (AED)',
                    'vat' => 'VAT',
                    'tax_amount' => 'Tax Amount',
                    'vat_charges' => 'VAT Charges'
                ]
            ]
        ];
    }
    
    /**
     * Validate extracted data
     */
    public function validateExtractedData(array $data): array
    {
        $errors = [];
        $warnings = [];
        
        // Check required fields - look in the structured data
        $electricityConsumption = null;
        if (isset($data['energy_consumption']['total_electricity_kwh'])) {
            $electricityConsumption = $data['energy_consumption']['total_electricity_kwh'];
        } elseif (isset($data['dewa_services']['electricity_consumption_kwh'])) {
            $electricityConsumption = $data['dewa_services']['electricity_consumption_kwh'];
        } elseif (isset($data['electricity_consumption_kwh'])) {
            $electricityConsumption = $data['electricity_consumption_kwh'];
        }
        
        if (empty($electricityConsumption)) {
            $errors[] = 'Electricity consumption (kWh) is required for carbon calculation';
        }
        
        // Validate electricity consumption
        if ($electricityConsumption && $electricityConsumption > 0) {
            if ($electricityConsumption > 10000) {
                $warnings[] = 'Electricity consumption seems unusually high';
            }
        }
        
        // Validate water consumption
        $waterConsumption = null;
        if (isset($data['energy_consumption']['total_water_cubic_meters'])) {
            $waterConsumption = $data['energy_consumption']['total_water_cubic_meters'];
        } elseif (isset($data['dewa_services']['water_consumption_cubic_meters'])) {
            $waterConsumption = $data['dewa_services']['water_consumption_cubic_meters'];
        } elseif (isset($data['water_consumption_cubic_meters'])) {
            $waterConsumption = $data['water_consumption_cubic_meters'];
        }
        
        if ($waterConsumption && $waterConsumption > 0) {
            if ($waterConsumption > 1000) {
                $warnings[] = 'Water consumption seems unusually high';
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
