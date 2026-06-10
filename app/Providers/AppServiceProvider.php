<?php

namespace App\Providers;

use App\Http\View\Composers\ConsultantAgencyComposer;
use App\Http\View\Composers\PlanGateComposer;
use App\Support\PlanGate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlanGate::class, function () {
            return PlanGate::forUser(Auth::guard('web')->user());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(
            [
                'layouts.app',
                'layouts.partials.nav-client',
                'reports.*',
                'disclosures.*',
                'quick-input.*',
            ],
            PlanGateComposer::class
        );

        View::composer('consultant.layouts.app', ConsultantAgencyComposer::class);
    }
}
