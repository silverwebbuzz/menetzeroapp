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
        // MENetZero 2.0 (Phase 0): every composer below is bound to BOTH the
        // existing view names and their 'theme-new::' equivalents.
        //
        // This is deliberate and load-bearing. Composers bind to view NAMES,
        // so a redesigned view would silently render without its composer
        // data — PlanGateComposer in particular, which would expose
        // plan-gated features to lower tiers. Logged as risk R-1 in
        // documentation/redesign.md. Any new-theme view path added in a
        // later phase MUST also be added here.
        View::composer(
            $this->withThemeViews([
                'layouts.app',
                'layouts.partials.nav-client',
                'reports.*',
                'disclosures.*',
                'quick-input.*',
            ]),
            PlanGateComposer::class
        );

        // Reporting-year dropdown options.
        //
        // 'layouts.app' is included so the topbar year selector (Phase B)
        // has its options on EVERY page, not only disclosure screens — the
        // reporting year is app-level context, so the control that changes
        // it must be available wherever the user is.
        View::composer(
            $this->withThemeViews([
                'layouts.app',
                'disclosures.*',
                'reports.*',
            ]),
            \App\Http\View\Composers\ReportingYearsComposer::class
        );

        View::composer(
            $this->withThemeViews(['consultant.layouts.app']),
            ConsultantAgencyComposer::class
        );
    }

    /**
     * Expand view patterns to cover every registered theme namespace.
     *
     * ['reports.*'] becomes ['reports.*', 'theme-new::reports.*'] so a
     * redesigned view receives exactly the same composer data as the view
     * it replaces. See risk R-1 in documentation/redesign.md.
     *
     * @param  array<int, string>  $views
     * @return array<int, string>
     */
    protected function withThemeViews(array $views): array
    {
        $expanded = $views;

        foreach ((array) config('themes.themes', []) as $theme) {
            $namespace = $theme['view_namespace'] ?? null;

            if ($namespace === null) {
                continue;
            }

            foreach ($views as $view) {
                $expanded[] = "{$namespace}::{$view}";
            }
        }

        return $expanded;
    }
}
