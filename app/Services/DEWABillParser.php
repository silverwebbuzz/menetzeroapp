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
        $text = '';
        
        // Method 1: Look for text between BT and ET markers (PDF text objects)
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Extract text from Tj and TJ operators
                preg_match_all('/\((.*?)\)\s*Tj/', $match, $textMatches);
                if (!empty($textMatches[1])) {
                    $text .= implode(' ', $textMatches[1]) . ' ';
                }
                
                // Also try TJ operators (array format)
                preg_match_all('/\[(.*?)\]\s*TJ/', $match, $tjMatches);
                if (!empty($tjMatches[1])) {
                    $text .= implode(' ', $tjMatches[1]) . ' ';
                }
            }
        }
        
        // Method 2: Look for readable text patterns in the PDF
        if (empty($text)) {
            // Extract text from parentheses (common in PDFs)
            preg_match_all('/\(([^)]+)\)/', $content, $parenMatches);
            if (!empty($parenMatches[1])) {
                $text .= implode(' ', $parenMatches[1]) . ' ';
            }
        }
        
        // Method 3: Look for text streams
        if (empty($text)) {
            preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $streamMatches);
            if (!empty($streamMatches[1])) {
                foreach ($streamMatches[1] as $stream) {
                    // Try to extract readable text from streams
                    $streamText = preg_replace('/[^\x20-\x7E]/', ' ', $stream);
                    $streamText = preg_replace('/\s+/', ' ', $streamText);
                    if (strlen(trim($streamText)) > 10) {
                        $text .= $streamText . ' ';
                    }
                }
            }
        }
        
        // Method 4: Look for common DEWA bill patterns
        if (empty($text)) {
            // Look for specific patterns that might be in the PDF
            $patterns = [
                '/DEWA/i',
                '/Electricity/i', 
                '/Water/i',
                '/Bill/i',
                '/Account/i',
                '/Customer/i',
                '/AED/i',
                '/kWh/i',
                '/Cubic/i'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $text .= 'DEWA Bill detected ';
                    break;
                }
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // If still empty, add a fallback message
        if (empty($text)) {
            $text = 'PDF text extraction failed - document may be image-based or encrypted';
        }
        
        return $text;
    }
    
    /**
     * Parse extracted text to find ALL bill data and organize into generic boxes
     */
    private function parseBillText(string $text): array
    {
        $billData = [
            'bill_information' => $this->extractBillInformation($text),
            'extracted_services' => $this->extractAllServices($text),
            'extracted_charges' => $this->extractAllCharges($text),
            'extracted_consumption' => $this->extractAllConsumption($text),
            'extracted_dates' => $this->extractAllDates($text),
            'extracted_amounts' => $this->extractAllAmounts($text),
            'raw_text' => $text,
            'confidence' => 60,
            'extraction_method' => 'pdf_text_extraction',
            'bill_type' => 'DEWA_UTILITY_BILL'
        ];
        
        return $billData;
    }
    
    /**
     * Extract basic bill information
     */
    private function extractBillInformation(string $text): array
    {
        $info = [];
        
        // Extract bill number
        if (preg_match('/\b(\d{9,12})\b/', $text, $matches)) {
            $info['bill_number'] = $matches[1];
        }
        
        // Extract account number
        if (preg_match('/Account[:\s]*(\d{8,12})/', $text, $matches)) {
            $info['account_number'] = $matches[1];
        }
        
        // Extract customer name
        if (preg_match('/Customer[:\s]*([A-Z\s]+)/', $text, $matches)) {
            $info['customer_name'] = trim($matches[1]);
        }
        
        return $info;
    }
    
    /**
     * Extract ALL services mentioned in the bill
     */
    private function extractAllServices(string $text): array
    {
        $services = [];
        
        // Look for electricity-related services
        if (preg_match_all('/(electricity|power|energy)[^0-9]*(\d+\.?\d*)\s*(kWh|units?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $services[] = [
                    'type' => 'Electricity',
                    'description' => trim($match[1]),
                    'value' => floatval($match[2]),
                    'unit' => $match[3],
                    'raw_text' => $match[0]
                ];
            }
        }
        
        // Look for water-related services
        if (preg_match_all('/(water|sewerage|drainage)[^0-9]*(\d+\.?\d*)\s*(cubic\s*meters?|m³|gallons?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $services[] = [
                    'type' => 'Water',
                    'description' => trim($match[1]),
                    'value' => floatval($match[2]),
                    'unit' => $match[3],
                    'raw_text' => $match[0]
                ];
            }
        }
        
        // Look for fuel/gas services
        if (preg_match_all('/(fuel|gas|petrol|diesel)[^0-9]*(\d+\.?\d*)\s*(liters?|gallons?|kg|tons?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $services[] = [
                    'type' => 'Fuel',
                    'description' => trim($match[1]),
                    'value' => floatval($match[2]),
                    'unit' => $match[3],
                    'raw_text' => $match[0]
                ];
            }
        }
        
        // Look for other services
        if (preg_match_all('/(municipality|housing|chiller|cooling|heating)[^0-9]*(\d+\.?\d*)\s*(AED|units?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $services[] = [
                    'type' => 'Other',
                    'description' => trim($match[1]),
                    'value' => floatval($match[2]),
                    'unit' => $match[3],
                    'raw_text' => $match[0]
                ];
            }
        }
        
        return $services;
    }
    
    /**
     * Extract ALL charges mentioned in the bill
     */
    private function extractAllCharges(string $text): array
    {
        $charges = [];
        
        // Extract all AED amounts with context
        if (preg_match_all('/([^0-9]*?)(\d+\.?\d*)\s*AED/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $context = trim($match[1]);
                $amount = floatval($match[2]);
                
                // Skip very small amounts (likely not relevant)
                if ($amount > 1) {
                    $charges[] = [
                        'description' => $context ?: 'Unspecified Charge',
                        'amount' => $amount,
                        'currency' => 'AED',
                        'raw_text' => $match[0]
                    ];
                }
            }
        }
        
        return $charges;
    }
    
    /**
     * Extract ALL consumption data
     */
    private function extractAllConsumption(string $text): array
    {
        $consumption = [];
        
        // Extract consumption with various units
        if (preg_match_all('/(\d+\.?\d*)\s*(kWh|kwh|units?|cubic\s*meters?|m³|liters?|gallons?|kg|tons?|m²|sq\s*ft)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $consumption[] = [
                    'value' => floatval($match[1]),
                    'unit' => $match[2],
                    'raw_text' => $match[0]
                ];
            }
        }
        
        return $consumption;
    }
    
    /**
     * Extract ALL dates mentioned in the bill
     */
    private function extractAllDates(string $text): array
    {
        $dates = [];
        
        // Extract various date formats
        if (preg_match_all('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/', $text, $matches)) {
            foreach ($matches[1] as $date) {
                $dates[] = [
                    'date' => $date,
                    'raw_text' => $date
                ];
            }
        }
        
        return $dates;
    }
    
    /**
     * Extract ALL amounts (numbers with context)
     */
    private function extractAllAmounts(string $text): array
    {
        $amounts = [];
        
        // Extract all numbers with context
        if (preg_match_all('/([^0-9]*?)(\d+\.?\d*)([^0-9]*?)/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $before = trim($match[1]);
                $number = floatval($match[2]);
                $after = trim($match[3]);
                
                // Only include meaningful numbers
                if ($number > 0 && (strlen($before) > 2 || strlen($after) > 2)) {
                    $amounts[] = [
                        'value' => $number,
                        'context_before' => $before,
                        'context_after' => $after,
                        'raw_text' => $match[0]
                    ];
                }
            }
        }
        
        return $amounts;
    }
    
    /**
     * Structure the extracted data for our system
     */
    private function structureBillData(array $rawData): array
    {
        return [
            // Bill Information
            'bill_information' => $rawData['bill_information'] ?? [],
            
            // All extracted services (Electricity, Water, Fuel, etc.)
            'extracted_services' => $rawData['extracted_services'] ?? [],
            
            // All extracted charges with context
            'extracted_charges' => $rawData['extracted_charges'] ?? [],
            
            // All consumption data with units
            'extracted_consumption' => $rawData['extracted_consumption'] ?? [],
            
            // All dates found in the bill
            'extracted_dates' => $rawData['extracted_dates'] ?? [],
            
            // All amounts with context
            'extracted_amounts' => $rawData['extracted_amounts'] ?? [],
            
            // Processing Information
            'processing_info' => [
                'confidence' => $rawData['confidence'] ?? 60,
                'extraction_method' => $rawData['extraction_method'] ?? 'pdf_text_extraction',
                'bill_type' => $rawData['bill_type'] ?? 'DEWA_UTILITY_BILL',
                'total_services_found' => count($rawData['extracted_services'] ?? []),
                'total_charges_found' => count($rawData['extracted_charges'] ?? []),
                'total_consumption_found' => count($rawData['extracted_consumption'] ?? [])
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
        
        // Check if we have any extracted data at all
        $hasServices = !empty($data['extracted_services'] ?? []);
        $hasCharges = !empty($data['extracted_charges'] ?? []);
        $hasConsumption = !empty($data['extracted_consumption'] ?? []);
        
        if (!$hasServices && !$hasCharges && !$hasConsumption) {
            $errors[] = 'No data extracted from the document';
        }
        
        // Check for electricity consumption in services or consumption data
        $hasElectricity = false;
        if ($hasServices) {
            foreach ($data['extracted_services'] as $service) {
                if (strtolower($service['type']) === 'electricity' || 
                    strpos(strtolower($service['description']), 'electricity') !== false ||
                    strpos(strtolower($service['unit']), 'kwh') !== false) {
                    $hasElectricity = true;
                    break;
                }
            }
        }
        
        if ($hasConsumption) {
            foreach ($data['extracted_consumption'] as $consumption) {
                if (strpos(strtolower($consumption['unit']), 'kwh') !== false) {
                    $hasElectricity = true;
                    break;
                }
            }
        }
        
        if (!$hasElectricity) {
            $warnings[] = 'No electricity consumption data found - may affect carbon calculations';
        }
        
        // Check for water consumption
        $hasWater = false;
        if ($hasServices) {
            foreach ($data['extracted_services'] as $service) {
                if (strtolower($service['type']) === 'water' || 
                    strpos(strtolower($service['description']), 'water') !== false ||
                    strpos(strtolower($service['unit']), 'cubic') !== false) {
                    $hasWater = true;
                    break;
                }
            }
        }
        
        if ($hasConsumption) {
            foreach ($data['extracted_consumption'] as $consumption) {
                if (strpos(strtolower($consumption['unit']), 'cubic') !== false) {
                    $hasWater = true;
                    break;
                }
            }
        }
        
        if (!$hasWater) {
            $warnings[] = 'No water consumption data found';
        }
        
        // Check for financial data
        if (!$hasCharges) {
            $warnings[] = 'No financial charges found in the document';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
