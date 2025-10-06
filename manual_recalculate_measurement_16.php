<?php

// Manual script to recalculate CO2e for measurement ID 16
// Run this to test the calculation

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Measurement;

echo "Manually recalculating CO2e for measurement ID 16...\n";

$measurement = Measurement::find(16);

if (!$measurement) {
    echo "Measurement ID 16 not found!\n";
    exit;
}

echo "Found measurement: " . $measurement->id . "\n";
echo "Current cached values:\n";
echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
echo "- emission_source_co2e: " . json_encode($measurement->emission_source_co2e ?? []) . "\n";

echo "\nTriggering recalculation...\n";
$result = $measurement->calculateAndCacheCo2e();

echo "Recalculation result:\n";
echo "- Total: " . $result['total'] . "\n";
echo "- Scope 1: " . $result['scope_1'] . "\n";
echo "- Sources: " . json_encode($result['sources']) . "\n";

echo "\nRefreshing measurement from database...\n";
$measurement->refresh();

echo "Updated cached values:\n";
echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
echo "- emission_source_co2e: " . json_encode($measurement->emission_source_co2e ?? []) . "\n";

echo "\nDone!\n";
