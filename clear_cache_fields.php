<?php

// Clear all cache fields since we're no longer using them
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Clearing cache fields from measurements table...\n\n";

try {
    // Clear all cache fields for all measurements
    $result = DB::statement("
        UPDATE measurements 
        SET total_co2e = NULL,
            scope_1_co2e = NULL,
            scope_2_co2e = NULL,
            scope_3_co2e = NULL,
            emission_source_co2e = NULL,
            co2e_calculated_at = NULL
    ");
    
    echo "Cache fields cleared: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Verify measurement 16
    $measurement = DB::table('measurements')->where('id', 16)->first();
    
    if ($measurement) {
        echo "\nMeasurement ID 16 after clearing:\n";
        echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
        echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
        echo "- emission_source_co2e: " . ($measurement->emission_source_co2e ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
