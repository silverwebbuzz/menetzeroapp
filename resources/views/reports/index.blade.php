@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@push('styles')
    <style>
        .card-body {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }

        /* Accordion styling */
        .accordion-icon {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-body {
            transition: all 0.35s ease;
        }

        .accordion-header:hover {
            background-color: #f3f4f6;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h1>Reports</h1>
            <p>Generate your carbon footprint report for a specific location and fiscal year. Export to Excel or PDF.</p>
        </div>
    </div>

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

    @if (isset($measurement) && $measurement)
        <div class="space-y-5">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Breakdown Chart</h2>
                        <p class="card-subtitle">Visualise emissions by scope or by source.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="btnScope" class="btn btn-primary btn-sm">
                            By Scope
                        </button>
                        <button id="btnEmission" class="btn btn-secondary btn-sm">
                            By Emission Source
                        </button>
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

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Results Breakdown</h2>
                        <p class="card-subtitle">Click each scope to expand its emission sources.</p>
                    </div>
                    <a href="{{ route('reports.export.excel', [
                        'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                        'location_id' => $selectedLocationId ?? request('location_id'),
                    ]) }}" class="btn btn-primary btn-sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Excel
                    </a>
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
                            @php
                                $total = collect($resultsBreakdown)->sum('value');
                            @endphp
                            <tbody id="scopeAccordion">
                                @foreach ($resultsBreakdown as $index => $scope)
                                    {{-- Accordion Header (Scope row) --}}
                                    <tr class="accordion-header cursor-pointer" data-target="scope-panel-{{ $index }}"
                                        style="background: var(--canvas); font-weight: 600;">
                                        <td style="padding: 0.875rem 1rem;">
                                            <div class="flex items-center gap-2">
                                                <span class="accordion-icon inline-flex items-center" style="transition: transform 0.3s;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                                <span class="text-slate-900">{{ $scope['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right" style="padding: 0.875rem 1rem; font-weight: 600; color: var(--ink);">
                                            {{ number_format($scope['value'], 2) }}
                                        </td>
                                    </tr>

                                    {{-- Accordion Body (Children) --}}
                                    <tr id="scope-panel-{{ $index }}" class="accordion-body hidden">
                                        <td colspan="2" style="padding: 0; background: var(--surface);">
                                            <table class="w-full">
                                                @foreach ($scope['children'] as $child)
                                                    <tr>
                                                        <td style="padding: 0.625rem 1rem 0.625rem 3rem; color: var(--ink-muted); border-top: 1px solid var(--line);">
                                                            {{ $child['name'] }}
                                                        </td>
                                                        <td class="text-right" style="padding: 0.625rem 1rem; color: var(--ink-muted); border-top: 1px solid var(--line);">
                                                            {{ number_format($child['value'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                    @if ($loop->last)
                                        <tr style="background: var(--brand-soft);">
                                            <td style="padding: 0.875rem 1rem; font-weight: 700; color: var(--ink); border-top: 2px solid var(--brand);">Total</td>
                                            <td class="text-right" style="padding: 0.875rem 1rem; font-weight: 700; color: var(--brand-darker); border-top: 2px solid var(--brand);">{{ number_format($total, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Emissions Summary + Offset --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Emissions Summary</h2>
                        <p class="card-subtitle">Totals by scope and offset options.</p>
                    </div>
                    <a href="{{ route('reports.export.pdf', [
                        'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                        'location_id' => $selectedLocationId ?? request('location_id'),
                    ]) }}" class="btn btn-secondary btn-sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m-3-3l3 3 3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        </svg>
                        Download PDF
                    </a>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                        {{-- Summary Table --}}
                        <div class="lg:col-span-2">
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <table class="w-full">
                                    <tbody>
                                        @foreach ($resultsBreakdown as $scope)
                                            <tr style="border-bottom: 1px solid var(--line);">
                                                <td style="padding: 0.75rem 1rem; font-weight: 500; color: var(--ink);">{{ $scope['name'] }}</td>
                                                <td class="text-right" style="padding: 0.75rem 1rem;">
                                                    <span class="font-semibold text-slate-900">{{ number_format($scope['value'], 2) }}</span>
                                                    <span class="text-xs font-medium text-slate-400 ml-1">tCO₂e</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr style="background: var(--canvas);">
                                            <td style="padding: 0.875rem 1rem; font-weight: 700; color: var(--ink);">Total</td>
                                            <td class="text-right" style="padding: 0.875rem 1rem;">
                                                <span class="font-bold text-slate-900 text-lg">{{ number_format($total, 2) }}</span>
                                                <span class="text-xs font-medium text-slate-400 ml-1">tCO₂e</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Offset Card --}}
                        <div class="rounded-lg p-5 border" style="background: linear-gradient(135deg, var(--brand-soft) 0%, #ffffff 100%); border-color: var(--brand-softer);">
                            <div class="text-xs uppercase tracking-wider text-brand-dark font-semibold mb-2">Total Emissions to Offset</div>
                            <div class="text-3xl font-bold text-brand-darker leading-none">{{ number_format($total, 2) }}</div>
                            <div class="text-xs font-medium text-slate-500 mt-1 mb-4">tCO₂e</div>
                            <button class="btn btn-primary w-full">
                                Offset Now
                            </button>
                        </div>
                    </div>
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

                    // Close all accordions except the clicked one
                    document.querySelectorAll('.accordion-body').forEach(body => {
                        if (body !== targetBody) body.classList.add('hidden');
                    });

                    document.querySelectorAll('.accordion-icon').forEach(i => {
                        if (i !== icon) i.style.transform = 'rotate(0deg) scale(1)';
                    });

                    // Toggle clicked accordion
                    const isOpen = !targetBody.classList.contains('hidden');
                    targetBody.classList.toggle('hidden', isOpen);

                    icon.style.transform = isOpen ? 'rotate(0deg) scale(1)' :
                        'rotate(90deg) scale(1.05)';
                });
            });

            // Optional: open first accordion by default
            // if (headers.length > 0) headers[0].click();
        });

        @if (isset($measurement) && $measurement)
            const ctx = document.getElementById('analysisPieChart');

            const scopeData = {
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                values: @json($scopePercentages),
                raw: @json($scopeRawValues),
                colors: ['#3FAE91', '#FFA726', '#9C6ADE']
            };

            const emissionSourceData = {
                labels: @json($emissionSourceData->pluck('label')),
                values: @json($emissionSourceData->pluck('percent')),
                raw: @json($emissionSourceData->pluck('raw')),
                colors: ['#3FAE91', '#FFA726', '#9C6ADE', '#1E88E5', '#E53935', '#43A047']
            };

            let chart = new Chart(ctx, {
                type: 'pie',
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
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 14
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 13
                            },
                            formatter: (value) => value + '%'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const rawValue = context.dataset.raw[index];
                                    return `${rawValue} (${context.raw}%)`;
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
                chart.data.datasets[0].backgroundColor = data.colors;
                chart.update();
            }

            function setActiveButton(active, inactive) {
                document.getElementById(active).classList.add('bg-green-500', 'text-white');
                document.getElementById(active).classList.remove('border');

                document.getElementById(inactive).classList.remove('bg-green-500', 'text-white');
                document.getElementById(inactive).classList.add('border');
            }
        @endif
    </script>
@endpush
