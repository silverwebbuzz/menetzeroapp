@php
    $sparklines = $chartData['sparklines'] ?? [];
    $yoy = $yearOverYear ?? ['current_year' => 0, 'previous_year' => 0, 'change_pct' => null];

    $sparklineTrend = function (array $points): array {
        if (count($points) < 2) {
            return [0.0, 'neutral'];
        }
        $previous = $points[count($points) - 2];
        $last = $points[count($points) - 1];
        if ($previous == 0) {
            return [0.0, 'neutral'];
        }
        $pct = round((($last - $previous) / $previous) * 100, 1);

        return [$pct, $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral')];
    };

    $kpiCards = [
        [
            'label' => 'Total Emissions',
            'value' => $kpis['total_emissions'] ?? 0,
            'spark' => $sparklines['total'] ?? [],
            'trend' => $kpis['monthly_change'] ?? 0,
            'compare' => 'vs last month',
        ],
        [
            'label' => 'Scope 1',
            'value' => $kpis['scope1_total'] ?? 0,
            'spark' => $sparklines['scope1'] ?? [],
            'trend' => $sparklineTrend($sparklines['scope1'] ?? [])[0],
            'compare' => 'vs prior month',
        ],
        [
            'label' => 'Scope 2',
            'value' => $kpis['scope2_total'] ?? 0,
            'spark' => $sparklines['scope2'] ?? [],
            'trend' => $sparklineTrend($sparklines['scope2'] ?? [])[0],
            'compare' => 'vs prior month',
        ],
        [
            'label' => 'Scope 3',
            'value' => $kpis['scope3_total'] ?? 0,
            'spark' => $sparklines['scope3'] ?? [],
            'trend' => $sparklineTrend($sparklines['scope3'] ?? [])[0],
            'compare' => 'vs prior month',
        ],
    ];

    $trendClass = fn ($trend) => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'neutral');
    $trendArrow = fn ($trend) => $trend > 0 ? '↑' : ($trend < 0 ? '↓' : '→');
@endphp

<div class="ent-dashboard">
    {{-- Page header REMOVED at the user's request: the title restated the
         nav, and the reporting-year select duplicated the year switcher the
         SHELL already renders in the topbar
         (layouts.partials.reporting-year-switcher, present in both themes).
         Locations / Quick Input / Reports are all reachable from the nav. --}}

    {{-- Boundary-change warning. Absolute emissions across years with a
         different organisational boundary are not like-for-like (GHG Protocol
         Ch.5) — say so rather than letting the user read a false trend. --}}
    @if(!($comparability['comparable'] ?? true) && !empty($comparability['reasons']))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
            <p class="text-sm font-semibold text-amber-900">
                {{ ($comparability['requires_recalculation'] ?? false) ? 'Base year recalculation may be required' : 'Years are not directly comparable' }}
            </p>
            <p class="text-sm text-amber-900 mt-1">{{ $comparability['message'] }}</p>
            <ul class="list-disc list-inside text-xs text-amber-800 mt-2">
                @foreach($comparability['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI cards REMOVED: Total / Scope 1 / Scope 2 / Scope 3 now live on
         the Environmental pillar dashboard (/environmental), which shows the
         same four figures plus Scope 3 category coverage. Keeping both meant
         the same numbers in two places, free to drift.

         Net Zero Progress below is NOT a duplicate and stays: the Overview
         pathway chart is on this page too, and /environmental has no net-zero
         section. --}}

    {{-- Net Zero Progress REMOVED: it duplicated the Overview pathway card
         rendered directly ABOVE it on this same page (dashboard.partials
         .esg-performance) -- same reduction target, same baseline, same
         target year, shown twice.

         Nothing was lost. The three figures only this panel carried are now
         on the pathway card:
           progress %  -> "N% toward target" in the card header
           years left  -> under the projection tile
           intensity   -> the Environmental dashboard's Intensity KPI
         Compliance Status is likewise replaced by Framework readiness on the
         pathway card's row, which covers five frameworks rather than four and
         links through to each. --}}

    {{-- Emissions Trend and Scope Breakdown REMOVED: both now live on
         /environmental, where the trend is stacked by scope and the scope mix
         is a donut over the same data. The canvases are gone; the shared
         enterprise-scripts partial guards every binding with
         `if (ctx && typeof Chart !== 'undefined')`, so its chart code simply
         no-ops rather than erroring. --}}

    {{-- Compliance Status REMOVED: replaced by Framework readiness on the
         Overview cards above, which shows five frameworks rather than four,
         uses DisclosureService's weighted percentages, and links through to
         each framework's own page. --}}

    {{-- AI Recommendations REMOVED at the user's request. $recommendations is
         still built by DashboardController and passed to the view; nothing
         else reads it, so it is inert rather than broken. --}}
</div>

@include('dashboard.partials.enterprise-scripts')
