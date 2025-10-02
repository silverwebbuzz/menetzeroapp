<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        
        // Run basic demo data seeder
        $this->call([
            UaeSchemaSeeder::class,
        ]);
        
        $this->command->info('✅ Basic demo data created');
        
        // Ask if user wants comprehensive data
        if ($this->command->confirm('Do you want to create comprehensive dataset (100+ companies)? This will take longer.', false)) {
            $this->call([
                ComprehensiveUaeSeeder::class,
            ]);
            $this->command->info('✅ Comprehensive dataset created');
        }
        
        $this->command->info('🎉 Database seeding completed!');
    }
}
