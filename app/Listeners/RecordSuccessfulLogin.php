<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stamps last_login_at / last_login_ip / login_count on every successful sign-in.
 *
 * Listens to Illuminate\Auth\Events\Login, which every guard fires -- web,
 * consultant and admin -- so one listener covers all three without touching
 * the login controllers.
 *
 * Writes with a query builder update rather than saving the model: the model
 * may have unsaved state, and a save() here would persist it as a side effect
 * of logging in. It also skips model events, so nothing observes a "login" as
 * a content change.
 */
class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user || !method_exists($user, 'getTable')) {
            return;
        }

        $table = $user->getTable();

        // Only the three tables the migration actually added columns to.
        if (!in_array($table, ['users', 'consultants', 'admins'], true)) {
            return;
        }

        try {
            DB::table($table)
                ->where('id', $user->getKey())
                ->update([
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                    'login_count' => DB::raw('COALESCE(login_count, 0) + 1'),
                ]);
        } catch (\Throwable $e) {
            // A tracking failure must never block a sign-in.
            Log::warning('Could not record login', [
                'table' => $table,
                'id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
