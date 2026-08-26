{{--
    Dashboard chart configuration — shared by BOTH themes.

    Extracted verbatim from dashboard/partials/enterprise.blade.php so the
    old and new Overview render identical charts from one source. Neither
    theme should define chart logic of its own; edit it here.

    Binds by canvas id: #monthlyEmissionsChart, #emissionsByScopeChart.
    Any theme rendering the Overview must use those exact ids.
--}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartGreen = ['#16a34a', '#10b981', '#22c55e'];
    const chartFill = 'rgba(22, 163, 74, 0.08)';

    @php
        // Annual measurements sit on a single month, so a 12-month line leaves a
        // lone spike. With more than one year of data, compare year against year
        // instead; with one year, keep the original monthly line.
        $useYearly = (bool) ($yearlyTrend['has_multiple'] ?? false);
        $trendLabels = $useYearly
            ? ($yearlyTrend['labels'] ?? [])
            : ($chartData['monthly_labels'] ?? []);
        $trendValues = $useYearly
            ? ($yearlyTrend['values'] ?? [])
            : ($chartData['monthly_emissions'] ?? []);
        $trendValues = collect($trendValues)
            ->map(fn ($v) => is_numeric($v) ? round((float) $v, 2) : 0)
            ->values()
            ->all();

        // Intensity overlays the yearly bars on a second axis, so growth-
        // adjusted performance is readable next to the absolute total.
        $showIntensity = $useYearly && ($yearlyTrend['has_intensity'] ?? false);
        $intensityValues = $showIntensity
            ? collect($yearlyTrend['intensity'] ?? [])->map(fn ($v) => is_numeric($v) ? round((float) $v, 4) : null)->values()->all()
            : [];
    @endphp
    const monthlyCtx = document.getElementById('monthlyEmissionsChart');
    if (monthlyCtx && typeof Chart !== 'undefined') {
        new Chart(monthlyCtx, {
            type: {!! $useYearly ? "'bar'" : "'line'" !!},
            data: {
                labels: {!! json_encode($trendLabels) !!},
                datasets: [{
                    label: 'Total Emissions (tCO₂e)',
                    data: {!! json_encode($trendValues) !!},
                    borderColor: chartGreen[0],
                    backgroundColor: {!! $useYearly ? "chartGreen[0]" : "chartFill" !!},
                    tension: 0.35,
                    fill: {!! $useYearly ? 'false' : 'true' !!},
                    pointRadius: 3,
                    pointBackgroundColor: chartGreen[0],
                    borderRadius: 6,
                    maxBarThickness: 72,
                    order: 2,
                }
                @if($showIntensity)
                , {
                    label: 'Intensity (tCO₂e per {{ $currentIntensity['unit'] ?? 'unit' }})',
                    data: {!! json_encode($intensityValues) !!},
                    type: 'line',
                    yAxisID: 'yIntensity',
                    borderColor: '#0f766e',
                    backgroundColor: '#0f766e',
                    tension: 0.35,
                    fill: false,
                    pointRadius: 4,
                    order: 1,
                }
                @endif
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: {!! $showIntensity ? 'true' : 'false' !!}, position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(15, 23, 42, 0.06)' },
                        ticks: { font: { size: 12 } },
                        title: { display: {!! $showIntensity ? 'true' : 'false' !!}, text: 'Absolute (tCO₂e)' },
                    },
                    @if($showIntensity)
                    yIntensity: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { font: { size: 12 } },
                        title: { display: true, text: 'Intensity' },
                    },
                    @endif
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
