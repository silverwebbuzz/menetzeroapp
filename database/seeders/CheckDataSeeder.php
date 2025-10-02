<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Facility;
use App\Models\EmissionFactor;
use App\Models\EnergyData;
use App\Models\TransportData;
use App\Models\IndustrialData;
use App\Models\WasteData;
use App\Models\AgricultureData;
use App\Models\Report;
use App\Models\Subscription;

class CheckDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔍 Checking current database data...');

        try {
            $companyCount = Company::count();
            $facilityCount = Facility::count();
            $emissionFactorCount = EmissionFactor::count();
            $energyDataCount = EnergyData::count();
            $transportDataCount = TransportData::count();
            $industrialDataCount = IndustrialData::count();
            $wasteDataCount = WasteData::count();
            $agricultureDataCount = AgricultureData::count();
            $reportCount = Report::count();
            $subscriptionCount = Subscription::count();

            $this->command->info('📊 Current Database Status:');
            $this->command->info('   Companies: ' . $companyCount);
            $this->command->info('   Facilities: ' . $facilityCount);
            $this->command->info('   Emission Factors: ' . $emissionFactorCount);
            $this->command->info('   Energy Data: ' . $energyDataCount);
            $this->command->info('   Transport Data: ' . $transportDataCount);
            $this->command->info('   Industrial Data: ' . $industrialDataCount);
            $this->command->info('   Waste Data: ' . $wasteDataCount);
            $this->command->info('   Agriculture Data: ' . $agricultureDataCount);
            $this->command->info('   Reports: ' . $reportCount);
            $this->command->info('   Subscriptions: ' . $subscriptionCount);

            if ($companyCount > 0) {
                $this->command->info('📋 Sample Companies:');
                $companies = Company::take(5)->get();
                foreach ($companies as $company) {
                    $this->command->info('   - ' . $company->name . ' (' . $company->emirate . ', ' . $company->sector . ')');
                }
            }

            if ($facilityCount > 0) {
                $this->command->info('🏢 Sample Facilities:');
                $facilities = Facility::with('company')->take(5)->get();
                foreach ($facilities as $facility) {
                    $this->command->info('   - ' . $facility->name . ' (' . $facility->type . ') - ' . $facility->company->name);
                }
            }

            $this->command->info('✅ Database check completed!');

        } catch (\Exception $e) {
            $this->command->error('❌ Error checking data: ' . $e->getMessage());
        }
    }
}
