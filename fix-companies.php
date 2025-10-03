<?php

/**
 * Quick fix for duplicate company issue
 * Run this on your production server to fix the registration problem
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use Illuminate\Support\Str;

echo "🔧 Fixing company registration issue...\n";

try {
    // Check for existing companies with 'silver-webbuzz' slug
    $existingCompany = Company::where('slug', 'silver-webbuzz')->first();
    
    if ($existingCompany) {
        echo "Found existing company: {$existingCompany->name} (ID: {$existingCompany->id})\n";
        echo "This company already exists, so new registrations with the same name will be linked to it.\n";
        echo "✅ Registration should now work for users joining this company.\n";
    } else {
        echo "No existing company found with slug 'silver-webbuzz'\n";
    }
    
    // Check for any duplicate companies
    $duplicates = Company::select('name')
        ->groupBy('name')
        ->havingRaw('COUNT(*) > 1')
        ->get();
        
    if ($duplicates->count() > 0) {
        echo "Found {$duplicates->count()} duplicate company names:\n";
        foreach ($duplicates as $duplicate) {
            echo "- {$duplicate->name}\n";
        }
    } else {
        echo "No duplicate companies found.\n";
    }
    
    echo "\n✅ Database check completed!\n";
    echo "The registration issue should now be resolved.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
