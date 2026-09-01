<?php

use App\Console\Commands\ApplyScheduledDowngradesCommand;
use App\Console\Commands\SendRenewalRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendRenewalRemindersCommand::class)->dailyAt('08:00');

// Runs before the reminder job: a term that ended overnight should be moved to
// its scheduled plan before anything emails the customer about renewing.
Schedule::command(ApplyScheduledDowngradesCommand::class)->dailyAt('07:30');
