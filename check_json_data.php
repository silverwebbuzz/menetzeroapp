<?php

// Check where the JSON data is coming from
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking JSON data in measurements table...\n\n";

try {
    // Get measurement 16 data
    $measurement = DB::table('measurements')->where('id', 16)->first();
    
    if ($measurement) {
        echo "Measurement ID 16:\n";
        echo "- total_co2e: " . ($measurement->total_co2e ?? 'NULL') . "\n";
        echo "- scope_1_co2e: " . ($measurement->scope_1_co2e ?? 'NULL') . "\n";
        echo "- emission_source_co2e: " . ($measurement->emission_source_co2e ?? 'NULL') . "\n";
        echo "- updated_at: " . ($measurement->updated_at ?? 'NULL') . "\n";
        
        // Check if there are any database triggers
        echo "\nChecking for database triggers...\n";
        $triggers = DB::select("SHOW TRIGGERS LIKE 'measurements'");
        if (empty($triggers)) {
            echo "No triggers found on measurements table.\n";
        } else {
            foreach ($triggers as $trigger) {
                echo "Trigger: " . $trigger->Trigger . " - " . $trigger->Event . " - " . $trigger->Timing . "\n";
            }
        }
        
        // Check recent measurement_data entries
        echo "\nRecent measurement_data entries for measurement 16:\n";
        $recentData = DB::table('measurement_data')
            ->where('measurement_id', 16)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        foreach ($recentData as $data) {
            echo "- Source ID: {$data->emission_source_id}, Field: {$data->field_name}, Value: {$data->field_value}, Updated: {$data->updated_at}\n";
        }
        
    } else {
        echo "Measurement ID 16 not found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
