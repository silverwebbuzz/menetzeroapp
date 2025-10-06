<?php

// Direct fix for measurement 16 - bypass all Laravel model issues
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Direct fix for measurement ID 16...\n";

try {
    // Get current values
    $measurement = DB::table('measurements')->where('id', 16)->first();
    
    echo "Current values:\n";
    echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
    echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
    echo "- emission_source_co2e: " . ($measurement->emission_source_co2e ?? 'NULL') . "\n";
    
    // Calculate correct values with proper rounding
    $scope1Co2e = round($measurement->scope_1_co2e ?? 0, 6);
    $scope2Co2e = round($measurement->scope_2_co2e ?? 0, 6);
    $scope3Co2e = round($measurement->scope_3_co2e ?? 0, 6);
    $totalCo2e = round($scope1Co2e + $scope2Co2e + $scope3Co2e, 6);
    
    // Create properly rounded JSON data
    $roundedSourceCo2e = [
        '5' => round(2, 6),
        '1' => round(3.57, 6),
        '2' => round(20.79, 6),
        '3' => round(1500, 6)
    ];
    
    echo "\nCalculated values:\n";
    echo "- total_co2e: {$totalCo2e}\n";
    echo "- scope_1_co2e: {$scope1Co2e}\n";
    echo "- scope_2_co2e: {$scope2Co2e}\n";
    echo "- scope_3_co2e: {$scope3Co2e}\n";
    echo "- emission_source_co2e: " . json_encode($roundedSourceCo2e) . "\n";
    
    // Update directly with SQL
    $result = DB::statement("
        UPDATE measurements 
        SET total_co2e = ?,
            scope_1_co2e = ?,
            scope_2_co2e = ?,
            scope_3_co2e = ?,
            emission_source_co2e = ?,
            co2e_calculated_at = NOW(),
            updated_at = NOW()
        WHERE id = 16
    ", [
        $totalCo2e,
        $scope1Co2e,
        $scope2Co2e,
        $scope3Co2e,
        json_encode($roundedSourceCo2e)
    ]);
    
    echo "\nUpdate result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Verify
    $updated = DB::table('measurements')->where('id', 16)->first();
    echo "\nUpdated values:\n";
    echo "- total_co2e: " . ($updated->total_co2e ?? 'NULL') . "\n";
    echo "- scope_1_co2e: " . ($updated->scope_1_co2e ?? 'NULL') . "\n";
    echo "- emission_source_co2e: " . ($updated->emission_source_co2e ?? 'NULL') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
