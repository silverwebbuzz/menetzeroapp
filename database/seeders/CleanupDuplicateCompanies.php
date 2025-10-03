<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use Illuminate\Support\Str;

class CleanupDuplicateCompanies extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧹 Cleaning up duplicate companies...');
        
        // Find companies with duplicate names
        $duplicates = Company::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();
            
        foreach ($duplicates as $duplicate) {
            $companies = Company::where('name', $duplicate->name)->get();
            
            $this->command->info("Found {$companies->count()} companies with name: {$duplicate->name}");
            
            // Keep the first one, delete the rest
            $keepCompany = $companies->first();
            $deleteCompanies = $companies->skip(1);
            
            foreach ($deleteCompanies as $company) {
                $this->command->info("Deleting duplicate company ID: {$company->id}");
                $company->delete();
            }
        }
        
        // Also check for companies with problematic slugs
        $problematicCompanies = Company::where('slug', 'like', '%silver%')
            ->orWhere('slug', 'like', '%webbuzz%')
            ->get();
            
        foreach ($problematicCompanies as $company) {
            $this->command->info("Found problematic company: {$company->name} (slug: {$company->slug})");
            
            // Update the slug to make it unique
            $newSlug = Str::slug($company->name) . '-' . $company->id;
            $company->update(['slug' => $newSlug]);
            $this->command->info("Updated slug to: {$newSlug}");
        }
        
        $this->command->info('✅ Company cleanup completed!');
    }
}
