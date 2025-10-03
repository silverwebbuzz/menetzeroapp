<?php

// Migration script to convert old emission boundaries data to new optimized format
// Run this after the new migration to preserve existing data

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Starting emission boundaries data migration...\n";

try {
    // Get all existing location_emission_boundaries data
    $existingData = Capsule::table('location_emission_boundaries')
        ->select('location_id', 'emission_source_id')
        ->get();

    if ($existingData->isEmpty()) {
        echo "✅ No existing data to migrate.\n";
        exit(0);
    }

    // Group by location_id and scope
    $groupedData = [];
    foreach ($existingData as $row) {
        // Get the scope from emission_sources_master
        $source = Capsule::table('emission_sources_master')
            ->where('id', $row->emission_source_id)
            ->first();
        
        if ($source) {
            $key = $row->location_id . '_' . $source->scope;
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'location_id' => $row->location_id,
                    'scope' => $source->scope,
                    'sources' => []
                ];
            }
            $groupedData[$key]['sources'][] = $row->emission_source_id;
        }
    }

    // Insert new format data
    $inserted = 0;
    foreach ($groupedData as $data) {
        Capsule::table('location_emission_boundaries_new')->insert([
            'location_id' => $data['location_id'],
            'scope' => $data['scope'],
            'selected_sources' => json_encode($data['sources']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inserted++;
    }

    echo "✅ Successfully migrated $inserted emission boundary records!\n";
    echo "📊 Data structure optimized:\n";
    echo "   - Old: " . $existingData->count() . " individual records\n";
    echo "   - New: $inserted grouped records\n";
    echo "   - Reduction: " . round((1 - $inserted / $existingData->count()) * 100, 1) . "% fewer records\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
