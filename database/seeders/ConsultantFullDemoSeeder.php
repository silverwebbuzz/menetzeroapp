<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Previously seeded Silver Webbuzz Sustainability Practice + consultant_50 + full
 * managed-client demo data. Disabled in CONSULTANT_MULTI_PACKAGE_PLAN Phase 2
 * (demo org wiped). Phase 5 will reintroduce a multi-package depth demo.
 *
 * Run: php artisan db:seed --class=ConsultantFullDemoSeeder
 */
class ConsultantFullDemoSeeder extends Seeder
{
    public const EMAIL = 'demo.full@menetzero.com';

    public const PASSWORD = 'FullDemo1!';

    public function run(): void
    {
        if ($this->command) {
            $this->command->warn(
                'ConsultantFullDemoSeeder is disabled (CONSULTANT_MULTI_PACKAGE_PLAN Phase 2). '
                .'The old consultant_50 / Silver Webbuzz demo was removed. '
                .'A new multi-package demo seeder will ship in Phase 5.'
            );
        }
    }
}
