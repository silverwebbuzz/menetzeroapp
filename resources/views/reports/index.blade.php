@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@push('styles')
    <style>
        .card-body {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .accordion-icon {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-body {
            transition: all 0.35s ease;
        }

        .accordion-header:hover {
            background-color: #f3f4f6;
        }

        .report-kpi {
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
            border: 1px solid #bbf7d0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: center;
        }

        .report-kpi.highlight {
            border-color: #059669;
            background: linear-gradient(135deg, #d1fae5 0%, #ffffff 100%);
        }

        .report-kpi .kpi-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 600;
        }

        .report-kpi .kpi-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #059669;
            line-height: 1.2;
            margin-top: 0.25rem;
        }

        .report-kpi .kpi-unit {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.125rem;
        }

        .moccae-notice {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-left: 4px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
            color: #78350f;
        }

        .report-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }

        .report-meta-item .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            font-weight: 600;
        }

        .report-meta-item .value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #111827;
            margin-top: 0.125rem;
        }

        .activity-table th {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .activity-table td {
            font-size: 0.8125rem;
            vertical-align: top;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>Reports</h1>
            <p>Generate your GHG inventory report for a specific location and fiscal year. Export to PDF or Excel for internal review or MOCCAE IEQT preparation.</p>
        </div>
    </div>

    @if(session('error') || isset($error))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') ?? $error }}
        </div>
    @endif

    <div class="card mb-5">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.show') }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                <div>
                    <label for="fiscal_year" class="form-label">Fiscal Year <span class="required">*</span></label>
                    <select name="fiscal_year" id="fiscal_year" required class="form-select">
                        <option value="">Select year…</option>
                        @if (isset($fiscalYears) && count($fiscalYears) > 0)
                            @foreach ($fiscalYears as $year)
                                <option value="{{ $year }}"
                                    {{ ($selectedFiscalYear ?? request('fiscal_year')) == $year ? 'selected' : '' }}>
                                    {{ $year }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label for="location_id" class="form-label">Location <span class="required">*</span></label>
                    <select name="location_id" id="location_id" required class="form-select">
                        <option value="">Select location…</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ ($selectedLocationId ?? request('location_id')) == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        Generate report
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (isset($measurement) && $measurement && isset($report))
        @php
            $totalTonnes = $report['total_tonnes'];
            $scopeTonnes = $report['scope_tonnes'];
            $resultsBreakdown = $report['results_breakdown'];
        @endphp

        {{-- Report header --}}
        <div class="card mb-5">
            <div class="card-header">
                <div>
                    <h2 class="card-title">GHG Inventory Summary</h2>
                    <p class="card-subtitle">{{ $company->name ?? '' }} — {{ $report['location']->name ?? '' }}</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('reports.export.pdf', [
                        'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                        'location_id' => $selectedLocationId ?? request('location_id'),
                    ]) }}" class="btn btn-primary btn-sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m-3-3l3 3 3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        </svg>
                        Download PDF
                    </a>
                    <a href="{{ route('reports.export.excel', [
                        'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                        'location_id' => $selectedLocationId ?? request('location_id'),
                    ]) }}" class="btn btn-secondary btn-sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body space-y-5">
                <div class="report-meta">
                    <div class="report-meta-item">
                        <div class="label">Reporting period</div>
                        <div class="value">{{ $report['reporting_period'] }}</div>
                    </div>
                    <div class="report-meta-item">
                        <div class="label">Fiscal year</div>
                        <div class="value">{{ $measurement->fiscal_year }}</div>
                    </div>
                    <div class="report-meta-item">
                        <div class="label">Status</div>
                        <div class="value">{{ ucfirst($measurement->status ?? 'draft') }}</div>
                    </div>
                    <div class="report-meta-item">
                        <div class="label">Activity entries</div>
                        <div class="value">{{ $report['entry_count'] }} records</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="report-kpi">
                        <div class="kpi-label">Scope 1</div>
                        <div class="kpi-value">{{ number_format($scopeTonnes['Scope 1'], 2) }}</div>
                        <div class="kpi-unit">tCO₂e direct</div>
                    </div>
                    <div class="report-kpi">
                        <div class="kpi-label">Scope 2</div>
                        <div class="kpi-value">{{ number_format($scopeTonnes['Scope 2'], 2) }}</div>
                        <div class="kpi-unit">tCO₂e energy</div>
                    </div>
                    <div class="report-kpi highlight">
                        <div class="kpi-label">Scope 1 + 2</div>
                        <div class="kpi-value">{{ number_format($report['scope_12_tonnes'], 2) }}</div>
                        <div class="kpi-unit">tCO₂e MOCCAE total</div>
                    </div>
                    <div class="report-kpi">
                        <div class="kpi-label">Grand total</div>
                        <div class="kpi-value">{{ number_format($totalTonnes, 2) }}</div>
                        <div class="kpi-unit">tCO₂e all scopes</div>
                    </div>
                </div>

                <div class="moccae-notice">
                    <strong>UAE official submission:</strong> {{ $report['methodology']['disclaimer'] }}
                    Register and submit at <a href="https://mrv.ae" target="_blank" rel="noopener" class="underline font-medium">mrv.ae</a> using the IEQT tool.
                </div>
            </div>
        </div>

        <div class="space-y-5">
            {{-- Chart --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Emissions Breakdown</h2>
                        <p class="card-subtitle">Visualise emissions by scope or by source category.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="btnScope" class="btn btn-primary btn-sm">By Scope</button>
                        <button id="btnEmission" class="btn btn-secondary btn-sm">By Source</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="flex justify-center">
                        <div class="w-full max-w-md">
                            <canvas id="analysisPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Results breakdown accordion --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Results Breakdown</h2>
                        <p class="card-subtitle">Click each scope to expand its emission sources. All values in tCO₂e.</p>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-wrap">
                        <table class="table" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th class="text-right">Results (tCO₂e)</th>
                                </tr>
                            </thead>
                            <tbody id="scopeAccordion">
                                @foreach ($resultsBreakdown as $index => $scope)
                                    @if($scope['tonnes'] > 0 || $scope['name'] !== 'Scope 3')
                                    <tr class="accordion-header cursor-pointer" data-target="scope-panel-{{ $index }}"
                                        style="background: var(--canvas); font-weight: 600;">
                                        <td style="padding: 0.875rem 1rem;">
                                            <div class="flex items-center gap-2">
                                                <span class="accordion-icon inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                                <span class="text-slate-900">{{ $scope['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right" style="padding: 0.875rem 1rem; font-weight: 600; color: var(--ink);">
                                            {{ number_format($scope['tonnes'], 2) }}
                                        </td>
                                    </tr>

                                    <tr id="scope-panel-{{ $index }}" class="accordion-body hidden">
                                        <td colspan="2" style="padding: 0; background: var(--surface);">
                                            <table class="w-full">
                                                @forelse ($scope['children'] as $child)
                                                    <tr>
                                                        <td style="padding: 0.625rem 1rem 0.625rem 3rem; color: var(--ink-muted); border-top: 1px solid var(--line);">
                                                            {{ $child['name'] }}
                                                        </td>
                                                        <td class="text-right" style="padding: 0.625rem 1rem; color: var(--ink-muted); border-top: 1px solid var(--line);">
                                                            {{ number_format($child['tonnes'], 2) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" style="padding: 0.625rem 1rem 0.625rem 3rem; color: var(--ink-muted); border-top: 1px solid var(--line);">
                                                            No source-level data
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </table>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                                <tr style="background: var(--brand-soft);">
                                    <td style="padding: 0.875rem 1rem; font-weight: 700; color: var(--ink); border-top: 2px solid var(--brand);">Total</td>
                                    <td class="text-right" style="padding: 0.875rem 1rem; font-weight: 700; color: var(--brand-darker); border-top: 2px solid var(--brand);">{{ number_format($totalTonnes, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Activity register --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Activity Data Register</h2>
                        <p class="card-subtitle">Line-by-line activity data, emission factors, and calculated emissions.</p>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    @if($report['activity_register']->isEmpty())
                        <p class="p-4 text-sm text-slate-500">No activity data recorded for this period.</p>
                    @else
                        <div class="table-wrap overflow-x-auto">
                            <table class="table activity-table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Scope</th>
                                        <th>Source / Activity</th>
                                        <th class="text-right">Quantity</th>
                                        <th>Unit</th>
                                        <th class="text-right">Factor</th>
                                        <th>Methodology</th>
                                        <th class="text-right">tCO₂e</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report['activity_register'] as $row)
                                    <tr>
                                        <td>{{ $row['entry_date'] }}</td>
                                        <td>{{ $row['scope'] }}</td>
                                        <td>
                                            <span class="font-medium">{{ $row['source'] }}</span>
                                            @if($row['activity'] !== $row['source'])
                                                <br><span class="text-slate-500 text-xs">{{ $row['activity'] }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ $row['quantity'] }}</td>
                                        <td>{{ $row['unit'] }}</td>
                                        <td class="text-right">
                                            {{ $row['factor_value'] }}
                                            <br><span class="text-xs text-slate-400">{{ $row['factor_unit'] }}</span>
                                        </td>
                                        <td class="text-xs">
                                            {{ $row['methodology'] }}
                                            @if($row['reference'] !== '—')
                                                <br><span class="text-slate-400">{{ $row['reference'] }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-medium">{{ number_format($row['tonnes'], 4) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Methodology --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Methodology</h2>
                        <p class="card-subtitle">Calculation standards and emission factor sources used in this inventory.</p>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="font-semibold text-slate-700">Framework</dt>
                            <dd class="text-slate-600 mt-1">{{ $report['methodology']['framework'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700">Emission factors</dt>
                            <dd class="text-slate-600 mt-1">{{ $report['methodology']['factors'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700">Scopes included</dt>
                            <dd class="text-slate-600 mt-1">{{ $report['methodology']['scopes'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700">GWP values</dt>
                            <dd class="text-slate-600 mt-1">{{ $report['methodology']['gwp'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const headers = document.querySelectorAll('.accordion-header');

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const targetId = header.dataset.target;
                    const targetBody = document.getElementById(targetId);
                    const icon = header.querySelector('.accordion-icon');

                    document.querySelectorAll('.accordion-body').forEach(body => {
                        if (body !== targetBody) body.classList.add('hidden');
                    });

                    document.querySelectorAll('.accordion-icon').forEach(i => {
                        if (i !== icon) i.style.transform = 'rotate(0deg) scale(1)';
                    });

                    const isOpen = !targetBody.classList.contains('hidden');
                    targetBody.classList.toggle('hidden', isOpen);

                    icon.style.transform = isOpen ? 'rotate(0deg) scale(1)' :
                        'rotate(90deg) scale(1.05)';
                });
            });
        });

        @if (isset($measurement) && $measurement && isset($report))
            const ctx = document.getElementById('analysisPieChart');

            const scopeData = {
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                values: @json($scopePercentages),
                raw: @json($scopeRawValues),
                colors: ['#059669', '#0284c7', '#9333ea']
            };

            const emissionSourceData = {
                labels: @json($emissionSourceData->pluck('label')),
                values: @json($emissionSourceData->pluck('percent')),
                raw: @json($emissionSourceData->map(fn($s) => number_format($s['tonnes'], 2))->values()),
                colors: ['#059669', '#0ea5a3', '#0284c7', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6']
            };

            let chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: scopeData.labels,
                    datasets: [{
                        data: scopeData.values,
                        raw: scopeData.raw,
                        backgroundColor: scopeData.colors,
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: chartOptions(),
                plugins: [ChartDataLabels]
            });

            function chartOptions() {
                return {
                    responsive: true,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 14 }
                        },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 12 },
                            formatter: (value) => value > 0 ? value + '%' : ''
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const rawValue = context.dataset.raw[index];
                                    return `${context.label}: ${rawValue} tCO₂e (${context.raw}%)`;
                                }
                            }
                        }
                    }
                }
            }

            document.getElementById('btnScope').addEventListener('click', () => {
                setActiveButton('btnScope', 'btnEmission');
                updateChart(scopeData);
            });

            document.getElementById('btnEmission').addEventListener('click', () => {
                setActiveButton('btnEmission', 'btnScope');
                updateChart(emissionSourceData);
            });

            function updateChart(data) {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.values;
                chart.data.datasets[0].raw = data.raw;
                chart.data.datasets[0].backgroundColor = data.colors.slice(0, data.labels.length);
                chart.update();
            }

            function setActiveButton(active, inactive) {
                document.getElementById(active).classList.remove('btn-secondary');
                document.getElementById(active).classList.add('btn-primary');
                document.getElementById(inactive).classList.remove('btn-primary');
                document.getElementById(inactive).classList.add('btn-secondary');
            }
        @endif
    </script>
@endpush
