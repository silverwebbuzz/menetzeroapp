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
     * Extract text from PDF file with enhanced methods
     */
    private function extractTextFromPDF(string $filePath): string
    {
        try {
            Log::info('Starting enhanced PDF text extraction for: ' . $filePath);
            
            // Check if file exists
            if (!file_exists($filePath)) {
                Log::error('PDF file does not exist: ' . $filePath);
                return '';
            }
            
            $fileSize = filesize($filePath);
            Log::info('PDF file size: ' . $fileSize . ' bytes');
            
            // Method 1: Try pdftotext command with multiple options
            if (function_exists('shell_exec') && $this->commandExists('pdftotext')) {
                Log::info('Attempting pdftotext extraction with multiple options');
                
                // Try different pdftotext options
                $pdftotextOptions = [
                    'pdftotext -layout -nopgbrk "' . $filePath . '" -',
                    'pdftotext -raw -nopgbrk "' . $filePath . '" -',
                    'pdftotext -table -nopgbrk "' . $filePath . '" -',
                    'pdftotext -lineprinter -nopgbrk "' . $filePath . '" -'
                ];
                
                foreach ($pdftotextOptions as $option) {
                    Log::info('Trying pdftotext option: ' . $option);
                    $output = shell_exec($option);
                    
                    if ($output && strlen(trim($output)) > 50) {
                        $output = $this->cleanTextForUTF8($output);
                        
                        // Check if output is readable (not binary)
                        if (!$this->isBinaryContent($output)) {
                            Log::info('pdftotext successful with ' . strlen($output) . ' characters');
                            Log::info('Sample output: ' . substr($output, 0, 200));
                            return $output;
                        } else {
                            Log::warning('pdftotext output appears to be binary content');
                        }
                    }
                }
                
                Log::warning('All pdftotext options failed or returned binary content');
            } else {
                Log::info('pdftotext not available, using fallback methods');
            }
            
            // Method 2: Try pdfinfo to get document info first
            if (function_exists('shell_exec') && $this->commandExists('pdfinfo')) {
                Log::info('Getting PDF info with pdfinfo');
                $pdfInfo = shell_exec('pdfinfo "' . $filePath . '"');
                if ($pdfInfo) {
                    Log::info('PDF Info: ' . substr($pdfInfo, 0, 300));
                    
                    // Check if PDF is encrypted
                    if (strpos($pdfInfo, 'Encrypted: yes') !== false) {
                        Log::warning('PDF appears to be encrypted');
                        return $this->createEncryptedPDFFallback();
                    }
                }
            }
            
            // Method 3: Enhanced binary content extraction
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \Exception('Could not read PDF file');
            }
            
            Log::info('Attempting enhanced binary content extraction');
            $text = $this->enhancedPDFTextExtraction($content);
            
            // Clean the extracted text for UTF-8 encoding
            $text = $this->cleanTextForUTF8($text);
            
            Log::info('Enhanced extraction result: ' . strlen($text) . ' characters');
            if (strlen($text) > 0) {
                Log::info('Sample output: ' . substr($text, 0, 200));
                
                // Check if the extracted text is readable
                if ($this->isBinaryContent($text)) {
                    Log::warning('Enhanced extraction still returned binary content');
                    return $this->createImageBasedPDFFallback();
                }
            }
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error('PDF text extraction failed: ' . $e->getMessage());
            return $this->createExtractionFailedFallback();
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
     * Clean text to ensure proper UTF-8 encoding
     */
    private function cleanTextForUTF8(string $text): string
    {
        // Remove null bytes and control characters
        $text = str_replace(["\0", "\x00"], '', $text);
        
        // Remove other control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Convert to UTF-8 and remove invalid sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove any remaining invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Clean up extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Clean extracted data to ensure all string values are UTF-8 safe
     */
    private function cleanExtractedDataForUTF8(array $data): array
    {
        return $this->recursiveCleanUTF8($data);
    }
    
    /**
     * Recursively clean UTF-8 encoding in arrays
     */
    private function recursiveCleanUTF8($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleaned[$key] = $this->recursiveCleanUTF8($value);
            }
            return $cleaned;
        } elseif (is_string($data)) {
            return $this->cleanTextForUTF8($data);
        } else {
            return $data;
        }
    }
    
    /**
     * Enhanced PDF text extraction with better pattern matching
     */
    private function enhancedPDFTextExtraction(string $content): string
    {
        $text = '';
        
        Log::info('Starting enhanced PDF text extraction');
        
        // Method 1: Look for text between BT and ET markers (PDF text objects)
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);
        Log::info('Found ' . count($matches[1]) . ' text objects');
        
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
        
        // Method 2: Look for DEWA-specific patterns in the PDF
        if (empty($text)) {
            Log::info('Looking for DEWA-specific patterns');
            $dewaPatterns = [
                '/DEWA/i',
                '/Dubai Electricity/i',
                '/Electricity/i', 
                '/Water/i',
                '/Bill/i',
                '/Account/i',
                '/Customer/i',
                '/AED/i',
                '/kWh/i',
                '/Cubic/i',
                '/Municipality/i',
                '/Sewerage/i',
                '/Drainage/i'
            ];
            
            $foundPatterns = [];
            foreach ($dewaPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $foundPatterns[] = $pattern;
                    Log::info('Found DEWA pattern: ' . $pattern);
                }
            }
            
            if (!empty($foundPatterns)) {
                $text .= 'DEWA Bill detected - Found patterns: ' . implode(', ', $foundPatterns) . ' ';
                Log::info('DEWA patterns found: ' . implode(', ', $foundPatterns));
                
                // Try to extract more text around the DEWA patterns
                $text .= $this->extractTextAroundPatterns($content, $foundPatterns);
            }
        }
        
        // Method 3: Look for readable text patterns in the PDF
        if (empty($text)) {
            Log::info('Looking for readable text patterns');
            // Extract text from parentheses (common in PDFs)
            preg_match_all('/\(([^)]+)\)/', $content, $parenMatches);
            if (!empty($parenMatches[1])) {
                $text .= implode(' ', $parenMatches[1]) . ' ';
                Log::info('Found ' . count($parenMatches[1]) . ' text patterns in parentheses');
            }
        }
        
        // Method 4: Look for text streams and try to decode them
        if (empty($text)) {
            Log::info('Looking for text streams');
            preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $streamMatches);
            Log::info('Found ' . count($streamMatches[1]) . ' streams');
            
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
        
        // Method 5: Look for compressed streams and try to extract text
        if (empty($text)) {
            Log::info('Looking for compressed streams');
            // Look for FlateDecode streams (compressed text)
            preg_match_all('/\/FlateDecode.*?stream\s*(.*?)\s*endstream/s', $content, $flateMatches);
            Log::info('Found ' . count($flateMatches[1]) . ' FlateDecode streams');
            
            if (!empty($flateMatches[1])) {
                foreach ($flateMatches[1] as $flateStream) {
                    // Try to find readable text in compressed streams
                    $readableText = $this->extractReadableTextFromStream($flateStream);
                    if (!empty($readableText)) {
                        $text .= $readableText . ' ';
                    }
                }
            }
        }
        
        // Method 6: Look for readable text in the raw content
        if (empty($text)) {
            Log::info('Looking for readable text in raw content');
            // Extract any readable text from the PDF content
            $readableText = $this->extractReadableTextFromContent($content);
            if (!empty($readableText)) {
                $text .= $readableText . ' ';
            }
        }
        
        // Method 7: Try to extract text from PDF objects
        if (empty($text)) {
            Log::info('Looking for PDF objects with text');
            // Look for PDF objects that might contain text
            preg_match_all('/\d+\s+\d+\s+obj\s*(.*?)\s*endobj/s', $content, $objMatches);
            Log::info('Found ' . count($objMatches[1]) . ' PDF objects');
            
            foreach ($objMatches[1] as $obj) {
                // Look for text content in objects
                if (preg_match('/\((.*?)\)/', $obj, $textMatches)) {
                    $text .= implode(' ', $textMatches[1]) . ' ';
                }
            }
        }
        
        // Method 8: Aggressive text extraction if still empty
        if (empty($text)) {
            Log::info('Normal extraction failed, trying aggressive extraction');
            $text = $this->aggressiveTextExtraction($content);
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        Log::info('Enhanced extraction completed with ' . strlen($text) . ' characters');
        
        return $text;
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
        
        // Method 3: Look for text streams and try to decode them
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
        
        // Method 4: Look for compressed streams and try to extract text
        if (empty($text)) {
            // Look for FlateDecode streams (compressed text)
            preg_match_all('/\/FlateDecode.*?stream\s*(.*?)\s*endstream/s', $content, $flateMatches);
            if (!empty($flateMatches[1])) {
                foreach ($flateMatches[1] as $flateStream) {
                    // Try to find readable text in compressed streams
                    $readableText = $this->extractReadableTextFromStream($flateStream);
                    if (!empty($readableText)) {
                        $text .= $readableText . ' ';
                    }
                }
            }
        }
        
        // Method 5: Look for common DEWA bill patterns in binary content
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
        
        // Method 6: Look for readable text in the raw content
        if (empty($text)) {
            // Extract any readable text from the PDF content
            $readableText = $this->extractReadableTextFromContent($content);
            if (!empty($readableText)) {
                $text .= $readableText . ' ';
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // If still empty, add a fallback message and create mock data for DEWA bills
        if (empty($text)) {
            $text = 'PDF text extraction failed - document may be image-based or encrypted';
            
            // For DEWA bills, create some mock data structure to help with manual processing
            Log::info('Creating fallback mock data for DEWA bill');
        }
        
        return $text;
    }
    
    /**
     * Extract readable text from compressed streams
     */
    private function extractReadableTextFromStream(string $stream): string
    {
        $text = '';
        
        // Look for readable text patterns in the stream
        $patterns = [
            '/[A-Za-z0-9\s\.\,\:\-\/]+/',  // Basic alphanumeric patterns
            '/\b[A-Z]{2,}\b/',              // Uppercase words (like DEWA, AED)
            '/\b\d+\.\d+\b/',              // Decimal numbers
            '/\b\d{4,}\b/',                // Long numbers (like account numbers)
        ];
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $stream, $matches);
            if (!empty($matches[0])) {
                $text .= implode(' ', $matches[0]) . ' ';
            }
        }
        
        return trim($text);
    }
    
    /**
     * Extract readable text from PDF content
     */
    private function extractReadableTextFromContent(string $content): string
    {
        $text = '';
        
        // Look for readable text patterns in the entire content
        $patterns = [
            '/[A-Za-z0-9\s\.\,\:\-\/]+/',  // Basic alphanumeric patterns
            '/\b[A-Z]{2,}\b/',              // Uppercase words
            '/\b\d+\.\d+\b/',              // Decimal numbers
            '/\b\d{4,}\b/',                // Long numbers
            '/\b[A-Za-z]+\s+\d+\b/',       // Word followed by number
        ];
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            if (!empty($matches[0])) {
                $text .= implode(' ', $matches[0]) . ' ';
            }
        }
        
        return trim($text);
    }
    
    /**
     * Extract text around found patterns
     */
    private function extractTextAroundPatterns(string $content, array $patterns): string
    {
        $text = '';
        
        foreach ($patterns as $pattern) {
            // Find all occurrences of the pattern
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
            
            foreach ($matches[0] as $match) {
                $position = $match[1];
                $matchText = $match[0];
                
                // Extract text around the match (100 characters before and after)
                $start = max(0, $position - 100);
                $end = min(strlen($content), $position + strlen($matchText) + 100);
                $context = substr($content, $start, $end - $start);
                
                // Clean the context and extract readable text
                $cleanContext = preg_replace('/[^\x20-\x7E\s]/', ' ', $context);
                $cleanContext = preg_replace('/\s+/', ' ', $cleanContext);
                $cleanContext = trim($cleanContext);
                
                if (strlen($cleanContext) > 10) {
                    $text .= $cleanContext . ' ';
                }
            }
        }
        
        return trim($text);
    }
    
    /**
     * More aggressive text extraction for difficult PDFs
     */
    private function aggressiveTextExtraction(string $content): string
    {
        $text = '';
        
        Log::info('Starting aggressive text extraction');
        
        // Method 1: Extract all text between parentheses
        preg_match_all('/\(([^)]+)\)/', $content, $parenMatches);
        if (!empty($parenMatches[1])) {
            foreach ($parenMatches[1] as $match) {
                $cleanMatch = preg_replace('/[^\x20-\x7E\s]/', ' ', $match);
                $cleanMatch = preg_replace('/\s+/', ' ', $cleanMatch);
                if (strlen(trim($cleanMatch)) > 3) {
                    $text .= trim($cleanMatch) . ' ';
                }
            }
        }
        
        // Method 2: Extract all text between square brackets
        preg_match_all('/\[([^\]]+)\]/', $content, $bracketMatches);
        if (!empty($bracketMatches[1])) {
            foreach ($bracketMatches[1] as $match) {
                $cleanMatch = preg_replace('/[^\x20-\x7E\s]/', ' ', $match);
                $cleanMatch = preg_replace('/\s+/', ' ', $cleanMatch);
                if (strlen(trim($cleanMatch)) > 3) {
                    $text .= trim($cleanMatch) . ' ';
                }
            }
        }
        
        // Method 3: Extract all readable sequences
        preg_match_all('/[A-Za-z0-9\s\.\,\:\-\/]{5,}/', $content, $sequenceMatches);
        if (!empty($sequenceMatches[0])) {
            foreach ($sequenceMatches[0] as $match) {
                $cleanMatch = preg_replace('/\s+/', ' ', $match);
                $cleanMatch = trim($cleanMatch);
                if (strlen($cleanMatch) > 5) {
                    $text .= $cleanMatch . ' ';
                }
            }
        }
        
        // Method 4: Look for specific DEWA bill content patterns
        $dewaContentPatterns = [
            '/Bill\s*No[\.:]?\s*(\d+)/i',
            '/Account\s*No[\.:]?\s*(\d+)/i',
            '/Customer\s*Name[\.:]?\s*([A-Za-z\s]+)/i',
            '/(\d+\.?\d*)\s*(kWh|kwh|units?)/i',
            '/(\d+\.?\d*)\s*(cubic\s*meters?|m³)/i',
            '/(\d+\.?\d*)\s*AED/i',
            '/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            // Additional DEWA-specific patterns
            '/electricity[^0-9]*(\d+\.?\d*)/i',
            '/water[^0-9]*(\d+\.?\d*)/i',
            '/sewerage[^0-9]*(\d+\.?\d*)/i',
            '/municipal[^0-9]*(\d+\.?\d*)/i',
            '/housing[^0-9]*(\d+\.?\d*)/i',
            '/chiller[^0-9]*(\d+\.?\d*)/i',
            '/consumption[^0-9]*(\d+\.?\d*)/i',
            '/usage[^0-9]*(\d+\.?\d*)/i'
        ];
        
        foreach ($dewaContentPatterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $text .= $match . ' ';
                }
            }
        }
        
        Log::info('Aggressive extraction found ' . strlen($text) . ' characters');
        
        return trim($text);
    }
    
    /**
     * Check if the extracted text is mostly binary content
     */
    private function isBinaryContent(string $text): bool
    {
        // If text is empty, it's not binary content
        if (empty($text)) {
            return false;
        }
        
        $totalLength = strlen($text);
        Log::info('Checking binary content for text of length: ' . $totalLength);
        
        // Count printable ASCII characters
        $printableCount = preg_match_all('/[\x20-\x7E]/', $text);
        $printableRatio = $printableCount / $totalLength;
        
        Log::info('Printable ratio: ' . round($printableRatio, 3) . ' (' . $printableCount . '/' . $totalLength . ')');
        
        // Check for common binary content patterns
        $binaryPatterns = [
            '/\?[^\s]*\?/',  // Question marks with non-space characters
            '/[^\x20-\x7E]{3,}/',  // 3+ consecutive non-printable characters
            '/endstream\s+endobj/',  // PDF stream markers
            '/obj\s+<<\/Type/',  // PDF object markers
            '/\x00/',  // Null bytes
            '/[\x80-\xFF]{3,}/',  // High ASCII characters (likely binary)
        ];
        
        $binaryMatchCount = 0;
        foreach ($binaryPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $binaryMatchCount++;
                Log::info('Found binary pattern: ' . $pattern);
            }
        }
        
        Log::info('Binary pattern matches: ' . $binaryMatchCount);
        
        // Check for PDF-specific binary indicators
        $pdfBinaryIndicators = [
            'stream',
            'endstream', 
            'obj',
            'endobj',
            'FlateDecode',
            'ASCIIHexDecode',
            'ASCII85Decode'
        ];
        
        $pdfIndicatorCount = 0;
        foreach ($pdfBinaryIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                $pdfIndicatorCount++;
            }
        }
        
        Log::info('PDF binary indicators found: ' . $pdfIndicatorCount);
        
        // If we have a low printable ratio OR multiple binary patterns OR many PDF indicators, it's binary content
        $isBinary = $printableRatio < 0.3 || $binaryMatchCount >= 2 || $pdfIndicatorCount >= 3;
        
        Log::info('Is binary content: ' . ($isBinary ? 'YES' : 'NO'));
        
        return $isBinary;
    }
    
    /**
     * Parse extracted text to find ALL bill data and organize into generic boxes
     */
    private function parseBillText(string $text): array
    {
        Log::info('Parsing bill text of length: ' . strlen($text));
        Log::info('Text content: ' . substr($text, 0, 200));
        
        // If no text was extracted or only binary content, create a fallback structure for manual processing
        if (empty($text) || strpos($text, 'PDF text extraction failed') !== false || $this->isBinaryContent($text)) {
            Log::info('No readable text extracted (binary content detected), creating fallback structure for manual processing');
            
            $billData = [
                'bill_information' => [
                    [
                        'type' => 'bill_number',
                        'label' => 'Bill Number',
                        'value' => 'Please enter bill number from your DEWA bill',
                        'confidence' => 0,
                        'help_text' => 'Look for "Bill No" or "Invoice No" on your bill'
                    ],
                    [
                        'type' => 'account_number', 
                        'label' => 'Account Number',
                        'value' => 'Please enter your DEWA account number',
                        'confidence' => 0,
                        'help_text' => 'Usually a 10-12 digit number on your bill'
                    ],
                    [
                        'type' => 'customer_name',
                        'label' => 'Customer Name', 
                        'value' => 'Please enter customer name from bill',
                        'confidence' => 0,
                        'help_text' => 'Name of the account holder'
                    ],
                    [
                        'type' => 'bill_date',
                        'label' => 'Bill Date',
                        'value' => 'Please enter bill date',
                        'confidence' => 0,
                        'help_text' => 'Date when the bill was issued'
                    ],
                    [
                        'type' => 'due_date',
                        'label' => 'Due Date',
                        'value' => 'Please enter due date',
                        'confidence' => 0,
                        'help_text' => 'Date when payment is due'
                    ]
                ],
                'extracted_services' => [
                    [
                        'type' => 'electricity',
                        'description' => 'Electricity Consumption - Please enter from your bill',
                        'unit' => 'kWh',
                        'value' => '0',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity consumption in kWh on your bill'
                    ],
                    [
                        'type' => 'water',
                        'description' => 'Water Consumption - Please enter from your bill', 
                        'unit' => 'Cubic Meters',
                        'value' => '0',
                        'confidence' => 0,
                        'help_text' => 'Look for water consumption in cubic meters on your bill'
                    ],
                    [
                        'type' => 'sewerage',
                        'description' => 'Sewerage Charges - Please enter from your bill',
                        'unit' => 'AED',
                        'value' => '0.00',
                        'confidence' => 0,
                        'help_text' => 'Look for sewerage or drainage charges on your bill'
                    ]
                ],
                'extracted_charges' => [
                    [
                        'type' => 'electricity_charge',
                        'description' => 'Electricity Charges - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity charges in AED on your bill'
                    ],
                    [
                        'type' => 'water_charge',
                        'description' => 'Water Charges - Please enter from your bill',
                        'amount' => '0.00', 
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for water charges in AED on your bill'
                    ],
                    [
                        'type' => 'sewerage_charge',
                        'description' => 'Sewerage Charges - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for sewerage or drainage charges in AED on your bill'
                    ],
                    [
                        'type' => 'municipal_fee',
                        'description' => 'Municipal Fee - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for municipal fee (5% of annual rent / 12) on your bill'
                    ]
                ],
                'extracted_consumption' => [
                    [
                        'type' => 'electricity_consumption',
                        'description' => 'Electricity Consumption - Please enter from your bill',
                        'unit' => 'kWh',
                        'value' => '0',
                        'period' => 'Current Month',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity consumption in kWh on your bill'
                    ],
                    [
                        'type' => 'water_consumption',
                        'description' => 'Water Consumption - Please enter from your bill',
                        'unit' => 'Cubic Meters', 
                        'value' => '0',
                        'period' => 'Current Month',
                        'confidence' => 0,
                        'help_text' => 'Look for water consumption in cubic meters on your bill'
                    ]
                ],
                'extracted_dates' => [
                    [
                        'type' => 'bill_date',
                        'label' => 'Bill Date',
                        'value' => 'Please enter bill date from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the date when the bill was issued'
                    ],
                    [
                        'type' => 'due_date',
                        'label' => 'Due Date', 
                        'value' => 'Please enter due date from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the payment due date on your bill'
                    ],
                    [
                        'type' => 'billing_period',
                        'label' => 'Billing Period',
                        'value' => 'Please enter billing period from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the period covered by this bill (e.g., Jan 1 - Jan 31)'
                    ]
                ],
                'extracted_amounts' => [
                    [
                        'type' => 'total_amount',
                        'label' => 'Total Amount',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for the total amount due in AED on your bill'
                    ],
                    [
                        'type' => 'previous_balance',
                        'label' => 'Previous Balance',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for any previous balance or outstanding amount'
                    ],
                    [
                        'type' => 'current_charges',
                        'label' => 'Current Charges',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for current period charges'
                    ]
                ],
                'raw_text' => $text,
                'confidence' => 30,
                'extraction_method' => 'fallback_manual_entry',
                'bill_type' => 'DEWA_UTILITY_BILL',
                'processing_info' => [
                    'extraction_failed' => true,
                    'requires_manual_entry' => true,
                    'message' => 'PDF text extraction failed - this appears to be an image-based or heavily compressed PDF. Please enter the data manually from your DEWA bill.',
                    'extraction_method' => 'manual_entry_required',
                    'pdf_type' => 'image_based_or_compressed',
                    'suggestions' => [
                        'This PDF appears to be scanned or heavily compressed',
                        'Please manually enter the data from your physical bill',
                        'Look for bill number, account number, and consumption data',
                        'Enter electricity and water consumption in the appropriate fields'
                    ]
                ]
            ];
            
            // Clean the fallback data as well
            $billData = $this->cleanExtractedDataForUTF8($billData);
            
            return $billData;
        }
        
        // Check if we have minimal text (like just "DEWA Bill detected")
        if (strlen(trim($text)) < 50 && strpos($text, 'DEWA Bill detected') !== false) {
            Log::info('Minimal text extracted, creating enhanced fallback structure');
            
            $billData = [
                'bill_information' => [
                    [
                        'type' => 'bill_number',
                        'label' => 'Bill Number',
                        'value' => 'Please enter bill number from your DEWA bill',
                        'confidence' => 0,
                        'help_text' => 'Look for "Bill No" or "Invoice No" on your bill'
                    ],
                    [
                        'type' => 'account_number', 
                        'label' => 'Account Number',
                        'value' => 'Please enter your DEWA account number',
                        'confidence' => 0,
                        'help_text' => 'Usually a 10-12 digit number on your bill'
                    ],
                    [
                        'type' => 'customer_name',
                        'label' => 'Customer Name', 
                        'value' => 'Please enter customer name from bill',
                        'confidence' => 0,
                        'help_text' => 'Name of the account holder'
                    ],
                    [
                        'type' => 'bill_date',
                        'label' => 'Bill Date',
                        'value' => 'Please enter bill date',
                        'confidence' => 0,
                        'help_text' => 'Date when the bill was issued'
                    ],
                    [
                        'type' => 'due_date',
                        'label' => 'Due Date',
                        'value' => 'Please enter due date',
                        'confidence' => 0,
                        'help_text' => 'Date when payment is due'
                    ]
                ],
                'extracted_services' => [
                    [
                        'type' => 'electricity',
                        'description' => 'Electricity Consumption - Please enter from your bill',
                        'unit' => 'kWh',
                        'value' => '0',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity consumption in kWh on your bill'
                    ],
                    [
                        'type' => 'water',
                        'description' => 'Water Consumption - Please enter from your bill', 
                        'unit' => 'Cubic Meters',
                        'value' => '0',
                        'confidence' => 0,
                        'help_text' => 'Look for water consumption in cubic meters on your bill'
                    ],
                    [
                        'type' => 'sewerage',
                        'description' => 'Sewerage Charges - Please enter from your bill',
                        'unit' => 'AED',
                        'value' => '0.00',
                        'confidence' => 0,
                        'help_text' => 'Look for sewerage or drainage charges on your bill'
                    ]
                ],
                'extracted_charges' => [
                    [
                        'type' => 'electricity_charge',
                        'description' => 'Electricity Charges - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity charges in AED on your bill'
                    ],
                    [
                        'type' => 'water_charge',
                        'description' => 'Water Charges - Please enter from your bill',
                        'amount' => '0.00', 
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for water charges in AED on your bill'
                    ],
                    [
                        'type' => 'sewerage_charge',
                        'description' => 'Sewerage Charges - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for sewerage or drainage charges in AED on your bill'
                    ],
                    [
                        'type' => 'municipal_fee',
                        'description' => 'Municipal Fee - Please enter from your bill',
                        'amount' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for municipal fee (5% of annual rent / 12) on your bill'
                    ]
                ],
                'extracted_consumption' => [
                    [
                        'type' => 'electricity_consumption',
                        'description' => 'Electricity Consumption - Please enter from your bill',
                        'unit' => 'kWh',
                        'value' => '0',
                        'period' => 'Current Month',
                        'confidence' => 0,
                        'help_text' => 'Look for electricity consumption in kWh on your bill'
                    ],
                    [
                        'type' => 'water_consumption',
                        'description' => 'Water Consumption - Please enter from your bill',
                        'unit' => 'Cubic Meters', 
                        'value' => '0',
                        'period' => 'Current Month',
                        'confidence' => 0,
                        'help_text' => 'Look for water consumption in cubic meters on your bill'
                    ]
                ],
                'extracted_dates' => [
                    [
                        'type' => 'bill_date',
                        'label' => 'Bill Date',
                        'value' => 'Please enter bill date from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the date when the bill was issued'
                    ],
                    [
                        'type' => 'due_date',
                        'label' => 'Due Date', 
                        'value' => 'Please enter due date from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the payment due date on your bill'
                    ],
                    [
                        'type' => 'billing_period',
                        'label' => 'Billing Period',
                        'value' => 'Please enter billing period from your bill',
                        'confidence' => 0,
                        'help_text' => 'Look for the period covered by this bill (e.g., Jan 1 - Jan 31)'
                    ]
                ],
                'extracted_amounts' => [
                    [
                        'type' => 'total_amount',
                        'label' => 'Total Amount',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for the total amount due in AED on your bill'
                    ],
                    [
                        'type' => 'previous_balance',
                        'label' => 'Previous Balance',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for any previous balance or outstanding amount'
                    ],
                    [
                        'type' => 'current_charges',
                        'label' => 'Current Charges',
                        'value' => '0.00',
                        'currency' => 'AED',
                        'confidence' => 0,
                        'help_text' => 'Look for current period charges'
                    ]
                ],
                'raw_text' => $text,
                'confidence' => 30,
                'extraction_method' => 'minimal_text_fallback',
                'bill_type' => 'DEWA_UTILITY_BILL',
                'processing_info' => [
                    'extraction_failed' => true,
                    'requires_manual_entry' => true,
                    'message' => 'PDF text extraction found minimal content. This appears to be an image-based or heavily compressed PDF. Please enter the data manually from your DEWA bill.',
                    'extraction_method' => 'manual_entry_required',
                    'pdf_type' => 'image_based_or_compressed',
                    'suggestions' => [
                        'This PDF appears to be scanned or heavily compressed',
                        'Please manually enter the data from your physical bill',
                        'Look for bill number, account number, and consumption data',
                        'Enter electricity and water consumption in the appropriate fields'
                    ]
                ]
            ];
            
            // Clean the fallback data as well
            $billData = $this->cleanExtractedDataForUTF8($billData);
            
            return $billData;
        }
        
        // Normal extraction process
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
        
        // Clean all string values in the extracted data to ensure UTF-8 encoding
        $billData = $this->cleanExtractedDataForUTF8($billData);
        
        return $billData;
    }
    
    /**
     * Extract basic bill information
     */
    private function extractBillInformation(string $text): array
    {
        $info = [];
        
        // Clean the text first to remove PDF artifacts
        $cleanText = $this->cleanExtractedText($text);
        
        // Extract bill number - look for various patterns
        $billPatterns = [
            '/Bill\s*No[\.:]?\s*(\d+)/i',
            '/Bill\s*Number[\.:]?\s*(\d+)/i',
            '/Invoice\s*No[\.:]?\s*(\d+)/i',
            '/\b(\d{8,12})\b/',  // Long numbers that might be bill numbers
        ];
        
        foreach ($billPatterns as $pattern) {
            if (preg_match($pattern, $cleanText, $matches)) {
                $info['bill_number'] = $matches[1];
                break;
            }
        }
        
        // Extract account number
        $accountPatterns = [
            '/Account\s*No[\.:]?\s*(\d+)/i',
            '/Account\s*Number[\.:]?\s*(\d+)/i',
            '/Account[\.:]?\s*(\d+)/i',
        ];
        
        foreach ($accountPatterns as $pattern) {
            if (preg_match($pattern, $cleanText, $matches)) {
                $info['account_number'] = $matches[1];
                break;
            }
        }
        
        // Extract customer name
        if (preg_match('/Customer\s*Name[\.:]?\s*([A-Za-z\s]+)/i', $cleanText, $matches)) {
            $info['customer_name'] = trim($matches[1]);
        }
        
        return $info;
    }
    
    /**
     * Clean extracted text to remove PDF artifacts and binary content
     */
    private function cleanExtractedText(string $text): string
    {
        Log::info('Cleaning extracted text of length: ' . strlen($text));
        
        // Remove PDF object references
        $text = preg_replace('/\b\d+\s+\d+\s+obj\b/', '', $text);
        $text = preg_replace('/\bendobj\b/', '', $text);
        
        // Remove stream markers
        $text = preg_replace('/\bstream\b/', '', $text);
        $text = preg_replace('/\bendstream\b/', '', $text);
        
        // Remove PDF operators
        $text = preg_replace('/\b(BT|ET|Tj|TJ|Tm|Td|TD|Tf|Tc|Tw|Tz|TL|Tr|Ts|T\*|Tj|TJ)\b/', '', $text);
        
        // Remove PDF filters
        $text = preg_replace('/\b(FlateDecode|ASCIIHexDecode|ASCII85Decode|LZWDecode|RunLengthDecode)\b/', '', $text);
        
        // Remove binary content markers
        $text = preg_replace('/\b(Filter|Length|Type|ExtGState|BM|Normal|ca)\b/', '', $text);
        
        // Remove common PDF artifacts
        $text = preg_replace('/\b(<<|>>|\/Type|\/Filter|\/Length|\/BM|\/Normal|\/ca)\b/', '', $text);
        
        // Remove PDF structure markers
        $text = preg_replace('/\b(<<|>>|\/Type|\/Subtype|\/Filter|\/Length|\/Width|\/Height|\/ColorSpace|\/BitsPerComponent)\b/', '', $text);
        
        // Remove binary content patterns
        $text = preg_replace('/[^\x20-\x7E\s]/', ' ', $text);
        
        // Remove excessive question marks and special characters
        $text = preg_replace('/\?{2,}/', '?', $text);
        $text = preg_replace('/[^\x20-\x7E\s\?\.\,\:\-\/]/', ' ', $text);
        
        // Clean up extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        Log::info('Cleaned text length: ' . strlen($text));
        
        return $text;
    }
    
    /**
     * Extract ALL services mentioned in the bill
     */
    private function extractAllServices(string $text): array
    {
        $services = [];
        
        Log::info('Extracting services from text of length: ' . strlen($text));
        
        // Clean the text first
        $cleanText = $this->cleanExtractedText($text);
        
        // Look for electricity-related services with enhanced patterns
        $electricityPatterns = [
            '/(electricity|power|energy)[^0-9]*(\d+\.?\d*)\s*(kWh|units?)/i',
            '/(\d+\.?\d*)\s*(kWh|units?)\s*(electricity|power|energy)/i',
            '/electricity[^0-9]*(\d+\.?\d*)\s*(kWh|units?)/i',
            '/power[^0-9]*(\d+\.?\d*)\s*(kWh|units?)/i',
            // Additional patterns for DEWA bills
            '/(\d+\.?\d*)\s*(kWh|kwh)/i',
            '/electricity[^0-9]*(\d+\.?\d*)/i',
            '/power[^0-9]*(\d+\.?\d*)/i',
            '/energy[^0-9]*(\d+\.?\d*)/i'
        ];
        
        foreach ($electricityPatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $services[] = [
                        'type' => 'Electricity',
                        'description' => trim($match[1] ?? 'Electricity'),
                        'value' => floatval($match[2] ?? $match[1]),
                        'unit' => $match[3] ?? $match[2],
                        'raw_text' => $match[0],
                        'confidence' => 0.8
                    ];
                }
            }
        }
        
        // Look for water-related services with enhanced patterns
        $waterPatterns = [
            '/(water|sewerage|drainage)[^0-9]*(\d+\.?\d*)\s*(cubic\s*meters?|m³|gallons?)/i',
            '/(\d+\.?\d*)\s*(cubic\s*meters?|m³|gallons?)\s*(water|sewerage|drainage)/i',
            '/water[^0-9]*(\d+\.?\d*)\s*(cubic\s*meters?|m³)/i',
            '/sewerage[^0-9]*(\d+\.?\d*)\s*(cubic\s*meters?|m³)/i',
            // Additional patterns for DEWA bills
            '/(\d+\.?\d*)\s*(cubic\s*meters?|m³)/i',
            '/water[^0-9]*(\d+\.?\d*)/i',
            '/sewerage[^0-9]*(\d+\.?\d*)/i',
            '/drainage[^0-9]*(\d+\.?\d*)/i'
        ];
        
        foreach ($waterPatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $services[] = [
                        'type' => 'Water',
                        'description' => trim($match[1] ?? 'Water'),
                        'value' => floatval($match[2] ?? $match[1]),
                        'unit' => $match[3] ?? $match[2],
                        'raw_text' => $match[0],
                        'confidence' => 0.8
                    ];
                }
            }
        }
        
        // Look for fuel/gas services
        if (preg_match_all('/(fuel|gas|petrol|diesel)[^0-9]*(\d+\.?\d*)\s*(liters?|gallons?|kg|tons?)/i', $cleanText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $services[] = [
                    'type' => 'Fuel',
                    'description' => trim($match[1]),
                    'value' => floatval($match[2]),
                    'unit' => $match[3],
                    'raw_text' => $match[0],
                    'confidence' => 0.7
                ];
            }
        }
        
        // Look for DEWA-specific services
        $dewaServicePatterns = [
            '/(municipality|housing|chiller|cooling|heating)[^0-9]*(\d+\.?\d*)\s*(AED|units?)/i',
            '/(municipal|housing|chiller)[^0-9]*(\d+\.?\d*)\s*(AED|units?)/i',
            '/municipal[^0-9]*(\d+\.?\d*)\s*(AED|units?)/i',
            '/housing[^0-9]*(\d+\.?\d*)\s*(AED|units?)/i'
        ];
        
        foreach ($dewaServicePatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $services[] = [
                        'type' => 'Other',
                        'description' => trim($match[1]),
                        'value' => floatval($match[2]),
                        'unit' => $match[3],
                        'raw_text' => $match[0],
                        'confidence' => 0.6
                    ];
                }
            }
        }
        
        // Method: Try to categorize extracted amounts as services based on context
        if (empty($services)) {
            Log::info('No services found with direct patterns, trying context-based categorization');
            $services = $this->categorizeAmountsAsServices($cleanText);
        }
        
        Log::info('Found ' . count($services) . ' services');
        
        return $services;
    }
    
    /**
     * Extract ALL charges mentioned in the bill
     */
    private function extractAllCharges(string $text): array
    {
        $charges = [];
        
        Log::info('Extracting charges from text of length: ' . strlen($text));
        
        // Clean the text first
        $cleanText = $this->cleanExtractedText($text);
        
        // Extract all AED amounts with context using multiple patterns
        $chargePatterns = [
            '/([^0-9]*?)(\d+\.?\d*)\s*AED/i',
            '/([^0-9]*?)(\d+\.?\d*)\s*Dirhams?/i',
            '/([^0-9]*?)(\d+\.?\d*)\s*Dhs?/i',
            '/AED\s*(\d+\.?\d*)/i',
            '/Dirhams?\s*(\d+\.?\d*)/i'
        ];
        
        foreach ($chargePatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $context = trim($match[1] ?? '');
                    $amount = floatval($match[2] ?? $match[1]);
                    
                    // Skip very small amounts (likely not relevant)
                    if ($amount > 1) {
                        $charges[] = [
                            'description' => $context ?: 'Unspecified Charge',
                            'amount' => $amount,
                            'currency' => 'AED',
                            'raw_text' => $match[0],
                            'confidence' => 0.7
                        ];
                    }
                }
            }
        }
        
        // Look for specific DEWA charge patterns
        $dewaChargePatterns = [
            '/electricity[^0-9]*(\d+\.?\d*)\s*AED/i',
            '/water[^0-9]*(\d+\.?\d*)\s*AED/i',
            '/sewerage[^0-9]*(\d+\.?\d*)\s*AED/i',
            '/municipal[^0-9]*(\d+\.?\d*)\s*AED/i',
            '/housing[^0-9]*(\d+\.?\d*)\s*AED/i',
            '/chiller[^0-9]*(\d+\.?\d*)\s*AED/i'
        ];
        
        foreach ($dewaChargePatterns as $pattern) {
            if (preg_match_all($pattern, $cleanText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $charges[] = [
                        'description' => 'DEWA ' . ucfirst(trim($match[0])),
                        'amount' => floatval($match[1]),
                        'currency' => 'AED',
                        'raw_text' => $match[0],
                        'confidence' => 0.9
                    ];
                }
            }
        }
        
        Log::info('Found ' . count($charges) . ' charges');
        
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
     * Create fallback structure for encrypted PDFs
     */
    private function createEncryptedPDFFallback(): string
    {
        Log::info('Creating fallback structure for encrypted PDF');
        return 'PDF appears to be encrypted - manual entry required';
    }
    
    /**
     * Create fallback structure for image-based PDFs
     */
    private function createImageBasedPDFFallback(): string
    {
        Log::info('Creating fallback structure for image-based PDF');
        return 'PDF appears to be image-based (scanned document) - manual entry required';
    }
    
    /**
     * Create fallback structure when extraction completely fails
     */
    private function createExtractionFailedFallback(): string
    {
        Log::info('Creating fallback structure for failed extraction');
        return 'PDF text extraction failed - manual entry required';
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
