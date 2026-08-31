{{--
    MENetZero 2.0 — company Overview (Phase 5.2).

    Overrides dashboard/partials/enterprise.blade.php under the new theme.

    Consumes exactly the same controller data — no controller change (P3):
      $kpis $chartData $netZeroProgress $availableYears $selectedYear
      $yearlyTrend $currentIntensity $comparability $topSources
      $recentActivity $yearOverYear $compliance $recommendations $company

    Chart config is not duplicated here. Both themes include the shared
    partial dashboard/partials/enterprise-scripts.blade.php, so chart logic
    stays in one place and cannot drift between themes.
--}}
@php
    $sparklines = $chartData['sparklines'] ?? [];

    $sparklineTrend = function (array $points): float {
        if (count($points) < 2) {
            return 0.0;
        }
        $previous = $points[count($points) - 2];
        $last = $points[count($points) - 1];
        if ($previous == 0) {
            return 0.0;
        }

        return round((($last - $previous) / $previous) * 100, 1);
    };

    $kpiCards = [
        ['label' => 'Total emissions', 'value' => $kpis['total_emissions'] ?? 0,
         'trend' => $kpis['monthly_change'] ?? 0, 'compare' => 'vs last month'],
        ['label' => 'Scope 1', 'value' => $kpis['scope1_total'] ?? 0,
         'trend' => $sparklineTrend($sparklines['scope1'] ?? []), 'compare' => 'vs prior month'],
        ['label' => 'Scope 2', 'value' => $kpis['scope2_total'] ?? 0,
         'trend' => $sparklineTrend($sparklines['scope2'] ?? []), 'compare' => 'vs prior month'],
        ['label' => 'Scope 3', 'value' => $kpis['scope3_total'] ?? 0,
         'trend' => $sparklineTrend($sparklines['scope3'] ?? []), 'compare' => 'vs prior month'],
    ];

    // Emissions falling is good, so a negative trend is the positive class.
    $deltaClass = fn ($t) => $t > 0 ? 'mnz-kpi__delta--bad' : ($t < 0 ? 'mnz-kpi__delta--good' : '');
    $deltaArrow = fn ($t) => $t > 0 ? '↑' : ($t < 0 ? '↓' : '→');
@endphp

<div class="mnz-stack" data-pillar="e">

    {{-- Page header REMOVED at the user's request: the reporting-year select
         duplicated the shell's own year switcher, and the three actions are
         all reachable from the nav. --}}

    {{-- Boundary-change warning. Absolute emissions across years with a
         different organisational boundary are not like-for-like (GHG Protocol
         Ch.5). Preserved verbatim in meaning from the original. --}}
    @if (!($comparability['comparable'] ?? true) && !empty($comparability['reasons']))
        <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint)">
            <div class="mnz-panel__body">
                <strong style="color:var(--warn)">
                    {{ ($comparability['requires_recalculation'] ?? false)
                        ? 'Base year recalculation may be required'
                        : 'Years are not directly comparable' }}
                </strong>
                <p style="color:var(--warn);margin-top:4px">{{ $comparability['message'] }}</p>
                <ul style="margin:8px 0 0;padding-left:18px;color:var(--warn);font-size:12px">
                    @foreach ($comparability['reasons'] as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- KPI row REMOVED: Total / Scope 1 / Scope 2 / Scope 3 now live on the
         Environmental pillar dashboard (/environmental), which shows the same
         four figures plus Scope 3 category coverage.

         Net zero progress below is NOT a duplicate and stays. --}}

    {{-- Net zero progress REMOVED: it duplicated the Overview pathway card
         rendered directly ABOVE it on this same page -- same target, same
         baseline, same target year, twice.

         Nothing lost: progress % and years remaining are now on the pathway
         card; intensity is the Environmental dashboard's Intensity KPI. --}}

    {{-- Charts REMOVED: Emissions trend and Scope breakdown now live on
         /environmental. The shared enterprise-scripts partial guards each
         binding with `if (ctx && typeof Chart !== 'undefined')`, so removing
         the canvases makes its chart code no-op rather than error. --}}

    {{-- Compliance REMOVED: replaced by Framework readiness on the Overview
         cards above -- five frameworks rather than four, weighted percentages
         from DisclosureService, each linking to its own page. --}}

    {{-- Recommendations REMOVED at the user's request. $recommendations is
         still built by DashboardController; nothing reads it now, so it is
         inert rather than broken. --}}
</div>

{{-- Chart configuration, shared with the old theme. Both Overview partials
     include this same file, so chart logic lives in exactly one place and
     cannot drift between themes. --}}
@include('dashboard.partials.enterprise-scripts')
