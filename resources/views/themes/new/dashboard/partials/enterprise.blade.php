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

    {{-- KPI row --}}
    <div class="mnz-seam mnz-seam--4">
        @foreach ($kpiCards as $card)
            @php $trend = (float) ($card['trend'] ?? 0); @endphp
            <div class="mnz-kpi">
                <div class="mnz-label">{{ $card['label'] }}</div>
                <div class="mnz-kpi__value">
                    {{ co2e_t($card['value']) }}<span class="mnz-kpi__unit">tCO₂e</span>
                </div>
                <div class="mnz-kpi__delta {{ $deltaClass($trend) }}">
                    {{ $deltaArrow($trend) }} {{ abs($trend) }}%
                    <span class="mnz-muted">{{ $card['compare'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Net zero progress --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>Net zero progress</h3>
                <p class="mnz-muted">Reduction against your baseline year</p>
            </div>
            <div class="mnz-num">{{ $netZeroProgress['progress'] ?? 0 }}%</div>
        </div>
        <div class="mnz-panel__body">
            <div class="mnz-meter">
                <span style="width: {{ min(100, $netZeroProgress['progress'] ?? 0) }}%"></span>
            </div>

            <div class="mnz-seam mnz-seam--4" style="margin-top:16px">
                <div>
                    <div class="mnz-label">Current emissions</div>
                    <div class="mnz-num">{{ $netZeroProgress['current'] ?? 0 }}<span class="mnz-kpi__unit">tCO₂e</span></div>
                    {{-- Intensity normalises for growth: a company that added
                         sites can still show real improvement per unit. --}}
                    @if ($currentIntensity ?? null)
                        <div class="mnz-muted">
                            {{ number_format($currentIntensity['value'], 4) }} tCO₂e / {{ $currentIntensity['unit'] }}
                        </div>
                    @else
                        <div class="mnz-muted">
                            <a href="{{ route('settings.reporting') }}">Set an intensity denominator</a>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="mnz-label">Reduction</div>
                    <div class="mnz-num">{{ $netZeroProgress['reduction_pct'] ?? 0 }}%</div>
                </div>
                <div>
                    <div class="mnz-label">Target year</div>
                    <div class="mnz-num">{{ $netZeroProgress['target_year'] ?? 2050 }}</div>
                </div>
                <div>
                    <div class="mnz-label">Years remaining</div>
                    <div class="mnz-num">{{ $netZeroProgress['years_remaining'] ?? 25 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts. The canvas IDs must match the original exactly — the chart
         script pushed by the included partial binds to them by id. --}}
    <div class="mnz-cols mnz-cols--main">
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Emissions trend</h3>
                    <p class="mnz-muted">
                        {{ ($yearlyTrend['has_multiple'] ?? false) ? 'Year on year' : 'Rolling months' }}
                    </p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-chart"><canvas id="monthlyEmissionsChart"></canvas></div>
            </div>
        </div>

        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>Scope breakdown</h3>
                    <p class="mnz-muted">Share of total by scope</p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div class="mnz-chart mnz-chart--sm"><canvas id="emissionsByScopeChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Compliance --}}
    @if (!empty($compliance))
        <div>
            <div class="mnz-kicker" style="margin-bottom:10px">Compliance status</div>
            <div class="mnz-seam mnz-seam--4">
                @foreach ($compliance as $item)
                    <a href="{{ $item['url'] }}" class="mnz-panel" style="display:block">
                        <div class="mnz-panel__body">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                                <strong>{{ $item['title'] }}</strong>
                                <span class="mnz-chip {{ $item['status'] === 'complete' ? 'mnz-chip--ok' : ($item['status'] === 'pending' ? 'mnz-chip--warn' : '') }}">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </div>
                            <div class="mnz-meter mnz-meter--thin" style="margin-top:10px">
                                <span style="width: {{ min(100, $item['percent']) }}%"></span>
                            </div>
                            <div class="mnz-muted" style="margin-top:6px">{{ $item['percent'] }}% complete</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

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
