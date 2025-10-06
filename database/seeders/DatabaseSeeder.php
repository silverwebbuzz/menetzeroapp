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
        
        // Note: Emission factors should be inserted manually via SQL
        
        // Then run simple demo data
        $this->call([
            SimpleUaeSeeder::class,
        ]);
        
        $this->command->info('✅ Basic demo data created');
        
        // Ask if user wants more data
        if ($this->command->confirm('Do you want to create additional data? (20+ companies with activity data)', false)) {
            $this->call([
                BasicDataSeeder::class,
            ]);
            $this->command->info('✅ Additional data created');
        }
        
        $this->command->info('🎉 Database seeding completed!');
    }
}
