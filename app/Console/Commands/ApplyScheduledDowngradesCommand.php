<?php

namespace App\Console\Commands;

use App\Services\ScheduledDowngradeService;
use Illuminate\Console\Command;

class ApplyScheduledDowngradesCommand extends Command
{
    protected $signature = 'subscriptions:apply-scheduled-downgrades {--dry-run : Count due downgrades without applying them}';

    protected $description = 'Activate plan changes that were scheduled for the end of a paid term';

    public function handle(ScheduledDowngradeService $downgrades): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $downgrades->applyDue($dry);

        $this->info(($dry ? '[dry-run] ' : '') . sprintf(
            'Applied: %d · Skipped: %d · Failed: %d',
            $result['applied'],
            $result['skipped'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
