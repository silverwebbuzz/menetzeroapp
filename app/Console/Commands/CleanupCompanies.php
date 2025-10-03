<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Str;

class CleanupCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'companies:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate companies and fix slug conflicts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Cleaning up companies...');

        // Find and remove duplicate companies
        $duplicates = Company::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $companies = Company::where('name', $duplicate->name)->get();
            
            $this->info("Found {$companies->count()} companies with name: {$duplicate->name}");
            
            // Keep the first one, delete the rest
            $keepCompany = $companies->first();
            $deleteCompanies = $companies->skip(1);
            
            foreach ($deleteCompanies as $company) {
                $this->info("Deleting duplicate company ID: {$company->id}");
                $company->delete();
            }
        }

        // Fix problematic slugs
        $problematicCompanies = Company::where('slug', 'like', '%silver%')
            ->orWhere('slug', 'like', '%webbuzz%')
            ->get();
            
        foreach ($problematicCompanies as $company) {
            $this->info("Fixing slug for company: {$company->name} (current: {$company->slug})");
            
            $newSlug = Company::generateUniqueSlug($company->name);
            $company->update(['slug' => $newSlug]);
            $this->info("Updated slug to: {$newSlug}");
        }

        $this->info('✅ Company cleanup completed!');
        return 0;
    }
}
