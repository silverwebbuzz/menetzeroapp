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
        <div class="page-header-actions flex flex-wrap gap-2">
            <a href="{{ route('locations.index') }}" class="btn btn-secondary btn-sm">Locations</a>
            <a href="{{ route('quick-input.index') }}" class="btn btn-secondary btn-sm">Quick Input</a>
            <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm">Reports</a>
        </div>
    </div>

    {{-- Row 1: KPI cards --}}
    <div class="ent-grid-4">
        @foreach($kpiCards as $card)
            @php
                $trend = (float) ($card['trend'] ?? 0);
                $class = $trendClass($trend);
            @endphp
            <div class="ent-kpi-card">
                <div class="ent-kpi-card__head">
                    <span class="ent-label">{{ $card['label'] }}</span>
                    <x-ent-sparkline :points="$card['spark']" />
                </div>
                <div class="ent-kpi-value">
                    {{ co2e_t($card['value']) }}<span class="ent-kpi-unit">tCO₂e</span>
                </div>
                <div class="ent-kpi-card__trend {{ $class }}">
                    {{ $trendArrow($trend) }} {{ abs($trend) }}% {{ $card['compare'] }}
                </div>
                @if($loop->first && $yoy['change_pct'] !== null)
                    <div class="ent-kpi-card__compare">
                        Prior year: {{ number_format($yoy['previous_year'], 2) }} tCO₂e
                        ({{ $yoy['change_pct'] > 0 ? '+' : '' }}{{ $yoy['change_pct'] }}% YoY)
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Row 2: Net zero progress --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="ent-card-title">Net Zero Progress</h3>
                <p class="ent-card-subtitle">UAE Net Zero 2050 pathway and projected achievement</p>
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
                    <div class="ent-label">Current emissions</div>
                    <div class="ent-kpi-value" style="font-size:1.25rem;">{{ $netZeroProgress['current'] ?? 0 }}<span class="ent-kpi-unit">tCO₂e</span></div>
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

    {{-- Row 3 & 4: Charts --}}
    <div class="ent-grid-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="ent-card-title">Emissions Trend</h3>
                    <p class="ent-card-subtitle">12-month total emissions (tCO₂e)</p>
                </div>
            </div>
            <div class="card-body">
                <div style="height:16rem;">
                    <canvas id="monthlyEmissionsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="ent-card-title">Scope Breakdown</h3>
                    <p class="ent-card-subtitle">Share of total emissions by scope</p>
                </div>
            </div>
            <div class="card-body">
                <div style="height:16rem;">
                    <canvas id="emissionsByScopeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartGreen = ['#16a34a', '#10b981', '#22c55e'];
    const chartFill = 'rgba(22, 163, 74, 0.08)';

    const monthlyCtx = document.getElementById('monthlyEmissionsChart');
    if (monthlyCtx && typeof Chart !== 'undefined') {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['monthly_labels'] ?? []) !!},
                datasets: [{
                    label: 'Total Emissions (tCO₂e)',
                    data: {!! json_encode(collect($chartData['monthly_emissions'] ?? [])->map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : $v)->values()->all()) !!},
                    borderColor: chartGreen[0],
                    backgroundColor: chartFill,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: chartGreen[0],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(15, 23, 42, 0.06)' },
                        ticks: { font: { size: 12 } },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 12 } },
                    },
                },
            },
        });
    }

    const scopeCtx = document.getElementById('emissionsByScopeChart');
    if (scopeCtx && typeof Chart !== 'undefined') {
        new Chart(scopeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                datasets: [{
                    data: {!! json_encode([
                        co2e_tonne($kpis['scope1_total'] ?? 0),
                        co2e_tonne($kpis['scope2_total'] ?? 0),
                        co2e_tonne($kpis['scope3_total'] ?? 0),
                    ]) !!},
                    backgroundColor: chartGreen,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, font: { size: 12 } },
                    },
                },
            },
        });
    }
});
</script>
@endpush
