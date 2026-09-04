<?php

namespace App\Console\Commands;

use App\Models\ClientSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Who loses access when entitlement narrows to one reporting year.
 *
 * Entitlement used to be `started_at->year .. expires_at->year`, so any term
 * not starting on 1 January covered two calendar years. It is now the single
 * `reporting_year` on the row. That is the correct rule, but it is a TAKEAWAY
 * for anyone who had already entered data in the second year.
 *
 * Run this BEFORE deploying the narrower rule. Every row it lists is a company
 * that could open a support ticket, and the decision on each -- honour the
 * second year, or ask them to buy it -- is commercial, not technical.
 */
class AuditReportingYearImpactCommand extends Command
{
    protected $signature = 'subscriptions:audit-reporting-year
                            {--with-data : Only list companies that actually have measurements in the second year}';

    protected $description = 'List subscriptions that currently span two reporting years and would lose the second';

    public function handle(): int
    {
        $onlyWithData = (bool) $this->option('with-data');

        $spanning = ClientSubscription::query()
            ->whereRaw('YEAR(started_at) <> YEAR(expires_at)')
            ->with(['plan', 'company'])
            ->orderBy('company_id')
            ->get();

        if ($spanning->isEmpty()) {
            $this->info('No subscriptions span two calendar years. Nothing to reconcile.');

            return self::SUCCESS;
        }

        $rows = [];
        $atRisk = 0;

        foreach ($spanning as $subscription) {
            $keptYear = (int) ($subscription->reporting_year ?? $subscription->started_at->year);
            $lostYear = (int) $subscription->expires_at->year;

            if ($lostYear === $keptYear) {
                continue;
            }

            // Measurements key off location_id, not company_id -- see
            // Company::measurements() -- and carry their own fiscal_year, which
            // is the reporting year to compare against rather than a date. A
            // company with data in the year it is about to lose is the case
            // that actually hurts.
            $lostYearRecords = DB::table('measurements')
                ->join('locations', 'measurements.location_id', '=', 'locations.id')
                ->where('locations.company_id', $subscription->company_id)
                ->where('measurements.fiscal_year', $lostYear)
                ->count();

            if ($onlyWithData && $lostYearRecords === 0) {
                continue;
            }

            if ($lostYearRecords > 0) {
                $atRisk++;
            }

            $rows[] = [
                $subscription->company_id,
                mb_strimwidth((string) ($subscription->company->name ?? '—'), 0, 28, '…'),
                $subscription->plan->plan_name ?? '—',
                $subscription->started_at->format('Y-m-d'),
                $subscription->expires_at->format('Y-m-d'),
                $keptYear,
                $lostYear,
                $lostYearRecords ?: '—',
            ];
        }

        if (empty($rows)) {
            $this->info('No affected subscriptions under the current filter.');

            return self::SUCCESS;
        }

        $this->table(
            ['Company', 'Name', 'Plan', 'Term start', 'Term end', 'Keeps FY', 'Loses FY', 'Records in lost FY'],
            $rows
        );

        $this->newLine();
        $this->warn(sprintf('%d subscription(s) span two years; %d have data in the year they would lose.', count($rows), $atRisk));
        $this->line('Decide per company: grant the second year (set reporting_year, or issue a term for it) or ask them to buy it.');

        return self::SUCCESS;
    }
}
