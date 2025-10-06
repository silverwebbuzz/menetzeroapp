<?php

// Direct database update to fix total_co2e issue
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Direct database update for measurement ID 16...\n";

try {
    // Get the current values
    $measurement = DB::table('measurements')->where('id', 16)->first();
    
    if (!$measurement) {
        echo "Measurement ID 16 not found!\n";
        exit;
    }
    
    echo "Current values:\n";
    echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
    echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
    echo "- scope_2_co2e: " . ($measurement->scope_2_co2e ?? 'NULL') . "\n";
    echo "- scope_3_co2e: " . ($measurement->scope_3_co2e ?? 'NULL') . "\n";
    
    // Calculate the correct total and round to 6 decimal places
    $totalCo2e = round(($measurement->scope_1_co2e ?? 0) + ($measurement->scope_2_co2e ?? 0) + ($measurement->scope_3_co2e ?? 0), 6);
    
    echo "\nCalculated total (rounded to 6 decimals): {$totalCo2e}\n";
    
    // Update total_co2e directly
    $updated = DB::table('measurements')
        ->where('id', 16)
        ->update([
            'total_co2e' => $totalCo2e,
            'updated_at' => now()
        ]);
    
    echo "Direct update result: " . ($updated ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Verify the update
    $updatedMeasurement = DB::table('measurements')->where('id', 16)->first();
    
    echo "\nUpdated values:\n";
    echo "- total_co2e: " . ($updatedMeasurement->total_co2e ?? 'NULL') . "\n";
    echo "- scope_1_co2e: " . ($updatedMeasurement->scope_1_co2e ?? 'NULL') . "\n";
    echo "- scope_2_co2e: " . ($updatedMeasurement->scope_2_co2e ?? 'NULL') . "\n";
    echo "- scope_3_co2e: " . ($updatedMeasurement->scope_3_co2e ?? 'NULL') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
