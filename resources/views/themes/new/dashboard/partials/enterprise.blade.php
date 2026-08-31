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

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Overview</div>
            <h1>Dashboard</h1>
            <p class="mnz-lead">Emissions performance, net zero progress and compliance readiness.</p>
        </div>
        <div class="mnz-pagehead__actions">
            @if (count($availableYears ?? []) > 1)
                <form method="GET" action="{{ route('client.dashboard') }}">
                    <select name="fiscal_year" class="mnz-select" onchange="this.form.submit()"
                            aria-label="Reporting year">
                        @foreach ($availableYears as $availableYear)
                            <option value="{{ $availableYear }}" @selected($availableYear == ($selectedYear ?? null))>{{ $availableYear }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="mnz-btn">Go</button></noscript>
                </form>
            @endif
            <a href="{{ route('locations.index') }}" class="mnz-btn mnz-btn--ghost">Locations</a>
            <a href="{{ route('quick-input.index') }}" class="mnz-btn mnz-btn--soft">Quick Input</a>
            <a href="{{ route('reports.index') }}" class="mnz-btn mnz-btn--primary">Reports</a>
        </div>
    </div>

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

    {{-- Recommendations --}}
    @if (!empty($recommendations))
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Recommended actions</h3>
                    <p class="mnz-muted">Prioritised from your inventory and reporting gaps</p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-stack">
                    @foreach ($recommendations as $rec)
                        <div style="display:flex;gap:10px;align-items:flex-start">
                            <span class="mnz-dot mnz-dot--e" style="margin-top:6px"></span>
                            <span>{{ $rec['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

</div>

{{-- Chart configuration, shared with the old theme. Both Overview partials
     include this same file, so chart logic lives in exactly one place and
     cannot drift between themes. --}}
@include('dashboard.partials.enterprise-scripts')
