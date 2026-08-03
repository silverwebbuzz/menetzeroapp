<?php

namespace App\Console\Commands;

use App\Services\SubscriptionRenewalNudgeService;
use Illuminate\Console\Command;

class SendRenewalRemindersCommand extends Command
{
    protected $signature = 'subscriptions:send-renewal-reminders {--dry-run : Count eligible nudges without sending}';

    protected $description = 'Email company and consultant renewal nudges (offline Request CTAs) in the 45/14/3-day windows';

    public function handle(SubscriptionRenewalNudgeService $nudges): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $nudges->sendDueReminderEmails($dry);

        $this->info(($dry ? '[dry-run] ' : '') . sprintf(
            'Company: %d · Consultant: %d · Skipped: %d',
            $result['company_sent'],
            $result['consultant_sent'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
