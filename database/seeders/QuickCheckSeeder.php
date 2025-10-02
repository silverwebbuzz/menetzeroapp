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

class QuickCheckSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔍 Quick Database Check...');

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

        $this->command->info('📊 Database Summary:');
        $this->command->info('   🏢 Companies: ' . $companyCount);
        $this->command->info('   🏭 Facilities: ' . $facilityCount);
        $this->command->info('   📊 Emission Factors: ' . $emissionFactorCount);
        $this->command->info('   ⚡ Energy Data: ' . $energyDataCount);
        $this->command->info('   🚛 Transport Data: ' . $transportDataCount);
        $this->command->info('   🏭 Industrial Data: ' . $industrialDataCount);
        $this->command->info('   🗑️ Waste Data: ' . $wasteDataCount);
        $this->command->info('   🌾 Agriculture Data: ' . $agricultureDataCount);
        $this->command->info('   📄 Reports: ' . $reportCount);
        $this->command->info('   💳 Subscriptions: ' . $subscriptionCount);

        if ($companyCount > 0) {
            $this->command->info('');
            $this->command->info('🏢 Sample Companies:');
            $companies = Company::take(3)->get();
            foreach ($companies as $company) {
                $this->command->info('   • ' . $company->name . ' (' . $company->emirate . ', ' . $company->sector . ')');
            }
        }

        $this->command->info('');
        $this->command->info('✅ Database check completed!');
        
        if ($companyCount > 0) {
            $this->command->info('🎉 You have data in your database!');
        } else {
            $this->command->info('📝 No data found. Run a seeder to add data.');
        }
    }
}
