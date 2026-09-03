<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sweeps up rows left behind by company deletions made BEFORE
 * OrganisationDeletionService covered them.
 *
 * Three kinds of leftover, all invisible to a foreign key:
 *   consultants                -- agency_company_id is nullOnDelete, so a
 *                                 deleted agency left a working login whose
 *                                 agency link is now simply null
 *   invoices                   -- company_id has no foreign key at all
 *   admin_package_assignments  -- raw integer columns
 *
 * Defaults to a dry run. Nothing is deleted without --force.
 */
class CleanupOrphanedOrganisationData extends Command
{
    protected $signature = 'organisations:cleanup-orphans {--force : Actually delete, instead of only reporting}';

    protected $description = 'Find (and optionally remove) rows orphaned by past company deletions';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if (!$force) {
            $this->warn('Dry run — nothing will be deleted. Re-run with --force to apply.');
        }

        $companyIds = DB::table('companies')->pluck('id');
        $total = 0;

        $total += $this->sweep(
            'invoices',
            fn () => DB::table('invoices')->whereNotNull('company_id')->whereNotIn('company_id', $companyIds),
            $force
        );

        $total += $this->sweep(
            'admin_package_assignments',
            fn () => DB::table('admin_package_assignments')->whereNotIn('company_id', $companyIds),
            $force
        );

        $total += $this->orphanedConsultants($force);

        $this->newLine();
        $this->info($force
            ? "Done — {$total} orphaned row(s) removed."
            : "Done — {$total} orphaned row(s) found. Re-run with --force to remove them.");

        return self::SUCCESS;
    }

    /**
     * Consultants are reported rather than swept blindly: a null
     * agency_company_id is also the normal state of a consultant who signed up
     * but never had an agency workspace provisioned, and those are live
     * accounts. Only a profile whose agency id points at a company that no
     * longer exists is unambiguously an orphan -- but nullOnDelete has already
     * erased that evidence, so the remaining candidates are listed for a human
     * to confirm instead of deleted automatically.
     */
    protected function orphanedConsultants(bool $force): int
    {
        if (!Schema::hasTable('consultants') || !Schema::hasColumn('consultants', 'agency_company_id')) {
            return 0;
        }

        $candidates = DB::table('consultants')
            ->whereNull('agency_company_id')
            ->select('id', 'name', 'email', 'company_name', 'created_at')
            ->get();

        if ($candidates->isEmpty()) {
            $this->line('consultants: no profiles without an agency workspace.');
            return 0;
        }

        $this->newLine();
        $this->warn("consultants: {$candidates->count()} profile(s) have no agency workspace.");
        $this->line('These are either orphans from a deleted company, or sign-ups never provisioned.');
        $this->line('Review and remove the real orphans from the admin consultants list — this command will not guess.');

        $this->table(
            ['ID', 'Name', 'Email', 'Company', 'Created'],
            $candidates->map(fn ($c) => [$c->id, $c->name, $c->email, $c->company_name, $c->created_at])->all()
        );

        return 0;
    }

    protected function sweep(string $table, callable $query, bool $force): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $count = $query()->count();

        if ($count === 0) {
            $this->line("{$table}: clean.");
            return 0;
        }

        if ($force) {
            $query()->delete();
            $this->info("{$table}: removed {$count} orphaned row(s).");
        } else {
            $this->warn("{$table}: {$count} orphaned row(s) would be removed.");
        }

        return $count;
    }
}
