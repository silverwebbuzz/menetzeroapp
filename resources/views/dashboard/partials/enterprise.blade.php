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
    <div class="ent-page-header flex flex-wrap items-start justify-between gap-4 mb-2">
        <div>
            <h1 class="ent-page-title">Dashboard</h1>
            <p class="ent-page-lead">Executive snapshot of emissions performance, net zero progress, and compliance readiness.</p>
        </div>
        <div class="page-header-actions flex flex-wrap items-center gap-2">
            {{-- Reporting-year filter. Hidden when there is nothing to choose
                 between, so a single-year company sees no redundant control. --}}
            @if(count($availableYears ?? []) > 1)
                <form method="GET" action="{{ route('client.dashboard') }}" class="flex items-center gap-2 mr-1">
                    <label for="dashboard_year" class="text-sm text-gray-600">Year</label>
                    <select name="fiscal_year" id="dashboard_year"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            onchange="this.form.submit()">
                        @foreach($availableYears as $availableYear)
                            <option value="{{ $availableYear }}" @selected($availableYear == ($selectedYear ?? null))>{{ $availableYear }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-secondary btn-sm">Go</button></noscript>
                </form>
            @endif
            <a href="{{ route('locations.index') }}" class="btn btn-secondary btn-sm">Locations</a>
            <a href="{{ route('quick-input.index') }}" class="btn btn-secondary btn-sm">Quick Input</a>
            <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm">Reports</a>
        </div>
    </div>

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

    {{-- Row 2: Net zero progress --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="ent-card-title">Net Zero Progress</h3>
                <p class="ent-card-subtitle">
                    @if($netZeroProgress['has_target'] ?? false)
                        {{ $netZeroProgress['target_name'] }}
                        @if($netZeroProgress['scope_label'] ?? null) · {{ $netZeroProgress['scope_label'] }} @endif
                    @else
                        UAE Net Zero 2050 pathway and projected achievement
                    @endif
                </p>
            </div>
            <div class="text-right">
                <div class="ent-kpi-value" style="font-size:1.5rem;">{{ $netZeroProgress['progress'] ?? 0 }}%</div>
                <div class="ent-card-subtitle">toward baseline reduction</div>
            </div>
        </div>
        <div class="card-body">
            <div class="ent-progress-track">
                <div class="ent-progress-fill" style="width: {{ min(100, $netZeroProgress['progress'] ?? 0) }}%;"></div>
            </div>
            <div class="ent-netzero-metrics">
                <div>
                    <div class="ent-label">Current emissions (absolute)</div>
                    <div class="ent-kpi-value" style="font-size:1.25rem;">{{ $netZeroProgress['current'] ?? 0 }}<span class="ent-kpi-unit">tCO₂e</span></div>
                    {{-- Intensity normalises for growth: a company that added
                         sites can still show real improvement per unit. --}}
                    @if($currentIntensity ?? null)
                        <div class="ent-card-subtitle mt-1">
                            {{ number_format($currentIntensity['value'], 4) }} tCO₂e / {{ $currentIntensity['unit'] }}
                        </div>
                    @else
                        <div class="ent-card-subtitle mt-1">
                            <a href="{{ route('settings.reporting') }}" class="hover:underline">Set an intensity denominator</a>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="ent-label">Reduction</div>
                    <div class="ent-kpi-value" style="font-size:1.25rem;">{{ $netZeroProgress['reduction_pct'] ?? 0 }}%</div>
                </div>
                <div>
                    <div class="ent-label">Target year</div>
                    <div class="ent-kpi-value" style="font-size:1.25rem;">{{ $netZeroProgress['target_year'] ?? 2050 }}</div>
                </div>
                <div>
                    <div class="ent-label">Projected achievement</div>
                    <div class="font-semibold text-slate-900 mt-1">{{ $netZeroProgress['projected_achievement'] ?? '—' }}</div>
                    <div class="ent-card-subtitle">{{ $netZeroProgress['years_remaining'] ?? 25 }} years remaining</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Emissions Trend and Scope Breakdown REMOVED: both now live on
         /environmental, where the trend is stacked by scope and the scope mix
         is a donut over the same data. The canvases are gone; the shared
         enterprise-scripts partial guards every binding with
         `if (ctx && typeof Chart !== 'undefined')`, so its chart code simply
         no-ops rather than erroring. --}}

    {{-- Row 5: Compliance --}}
    <div>
        <h3 class="ent-card-title mb-3">Compliance Status</h3>
        <div class="ent-grid-4">
            @foreach($compliance ?? [] as $item)
                <a href="{{ $item['url'] }}" class="ent-compliance-card">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="font-semibold text-slate-900">{{ $item['title'] }}</span>
                        <span class="ent-status ent-status--{{ $item['status'] }}">{{ ucfirst($item['status']) }}</span>
                    </div>
                    <div class="ent-progress-track">
                        <div class="ent-progress-fill" style="width: {{ min(100, $item['percent']) }}%;"></div>
                    </div>
                    <div class="ent-card-subtitle mt-2">{{ $item['percent'] }}% complete</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Row 6: AI recommendations --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="ent-card-title">AI Recommendations</h3>
                <p class="ent-card-subtitle">Prioritized actions based on your inventory and reporting gaps</p>
            </div>
        </div>
        <div class="card-body">
            <ul class="ent-rec-list">
                @foreach($recommendations ?? [] as $rec)
                    <li class="ent-rec-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>{{ $rec['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@include('dashboard.partials.enterprise-scripts')
