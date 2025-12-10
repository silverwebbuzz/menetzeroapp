<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'menetzero@gmail.com'],
            [
                'name' => 'Bhavik Koradiya',
                'email' => 'menetzero@gmail.com',
                'password' => Hash::make('admin@123456'),
                'is_active' => true,
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: menetzero@gmail.com');
        $this->command->info('Password: admin@123456');
    }
}

