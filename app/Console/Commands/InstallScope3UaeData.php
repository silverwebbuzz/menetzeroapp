<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallScope3UaeData extends Command
{
    protected $signature = 'menetzero:install-scope3
                            {--force : Re-run even if 15 Scope 3 quick-input sources already exist}';

    protected $description = 'Install the 15 GHG Protocol Scope 3 quick-input sources, forms, and factors (UAE setup SQL)';

    public function handle(): int
    {
        if (!Schema::hasTable('emission_sources_master')) {
            $this->error('emission_sources_master table not found. Run migrations first.');

            return self::FAILURE;
        }

        $existing = DB::table('emission_sources_master')
            ->where('scope', 'Scope 3')
            ->where('is_quick_input', true)
            ->count();

        if ($existing >= 15 && !$this->option('force')) {
            $this->info("Scope 3 quick-input sources already installed ({$existing} found). Use --force to re-run SQL.");

            return self::SUCCESS;
        }

        $path = base_path('documentation/scope3_uae_setup.sql');
        if (!is_readable($path)) {
            $this->error("SQL file not found: {$path}");

            return self::FAILURE;
        }

        if (!$this->confirm('This will DELETE existing Scope 3 measurement_data and re-seed Scope 3 master data. Continue?')) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $sql = file_get_contents($path);
        DB::unprepared($sql);

        $count = DB::table('emission_sources_master')
            ->where('scope', 'Scope 3')
            ->where('is_quick_input', true)
            ->count();

        $this->info("Scope 3 UAE data installed. Quick-input Scope 3 sources: {$count}");

        return self::SUCCESS;
    }
}
