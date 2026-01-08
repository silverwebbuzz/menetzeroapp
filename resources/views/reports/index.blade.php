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
    <form method="GET" action="{{ route('reports.show') }}"
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="fiscal_year" class="block text-sm font-semibold text-gray-700 mb-2">Year <span
                        class="text-red-500">*</span></label>
                <select name="fiscal_year" id="fiscal_year" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                    <option value="">Select Year</option>
                    @if (isset($fiscalYears) && count($fiscalYears) > 0)
                        @foreach ($fiscalYears as $year)
                            <option value="{{ $year }}"
                                {{ ($selectedFiscalYear ?? request('fiscal_year')) == $year ? 'selected' : '' }}>
                                {{ $year }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="location_id" class="block text-sm font-semibold text-gray-700 mb-2">Location <span
                        class="text-red-500">*</span></label>
                <select name="location_id" id="location_id" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                    <option value="">Select Location</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}"
                            {{ ($selectedLocationId ?? request('location_id')) == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-shrink-0">
                <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-medium rounded-lg shadow-md hover:from-purple-700 hover:to-purple-800 hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 whitespace-nowrap">
                    Select
                </button>
            </div>
        </div>
    </form>

    @if (isset($measurement) && $measurement)
        <div class="grid grid-cols-1 gap-6">
            <div class="card">
                <div class="card-body p-6">
                    <h2 class="text-xl font-semibold mb-4">The Scope Chart</h2>

                    <!-- Toggle Buttons -->
                    <div class="flex gap-2 mb-6">
                        <button id="btnScope" class="px-4 py-2 bg-green-500 text-white rounded">
                            Scope
                        </button>

                        <button id="btnEmission" class="px-4 py-2 border rounded">
                            Emissions Source
                        </button>
                    </div>

                    <!-- Chart -->
                    <div class="flex justify-center">
                        <div class="w-[420px]">
                            <canvas id="analysisPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-6">
                    <div class="flex justify-between mb-2">
                        <h2 class="text-xl font-semibold mb-4">Results Breakdown</h2>
                        <div class="mb-4">
                            <a href="{{ route('reports.export.excel', [
                                'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                                'location_id' => $selectedLocationId ?? request('location_id'),
                            ]) }}"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 
               bg-gradient-to-r from-purple-600 to-purple-700 
               text-white font-semibold rounded-lg shadow-sm 
               hover:from-purple-700 hover:to-purple-800 hover:shadow-md
               transition-all duration-200">
                                Export to Excel
                            </a>

                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 rounded-lg">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-lg">
                                        Name
                                    </th>
                                    <th class="px-4 py-2 text-right font-semibold text-lg">
                                        Results (tCO₂e)
                                    </th>
                                </tr>
                            </thead>
                            @php
                                $total = collect($resultsBreakdown)->sum('value');
                            @endphp
                            <tbody id="scopeAccordion">
                                @foreach ($resultsBreakdown as $index => $scope)
                                    {{-- Accordion Header (Scope row) --}}
                                    <tr class="border-t font-semibold cursor-pointer bg-gray-50 accordion-header"
                                        data-target="scope-panel-{{ $index }}">
                                        <td class="px-4 py-5 text-600 flex items-center gap-2">
                                            <span
                                                class="accordion-icon inline-flex items-center transition-transform duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </span>

                                            {{ $scope['name'] }}
                                        </td>
                                        <td class="px-4 py-5 text-right text-gray-900">
                                            {{ number_format($scope['value'], 2) }}
                                        </td>
                                    </tr>

                                    {{-- Accordion Body (Children) --}}
                                    <tr id="scope-panel-{{ $index }}" class="accordion-body hidden">
                                        <td colspan="2" class="p-0">
                                            <table class="w-full">
                                                @foreach ($scope['children'] as $child)
                                                    <tr class="border-t">
                                                        <td class="px-4 py-5 pl-10 text-gray-700">
                                                            {{ $child['name'] }}
                                                        </td>
                                                        <td class="px-4 py-5 text-right text-gray-700">
                                                            {{ number_format($child['value'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                    @if ($loop->last)
                                        <tr>
                                            <td colspan="2" class="p-0">
                                                <table class="w-full">
                                                    <tr class="border-t">
                                                        <td class="px-4 py-2 pl-10 font-semibold text-xl">
                                                            Total
                                                        </td>
                                                        <td class="px-4 py-2 text-right font-semibold text-xl">
                                                            {{ number_format($total, 2) }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-6">
                    <div class="flex justify-between mb-2">
                        <h2 class="text-xl font-semibold mb-4">Emissions Summary</h2>
                        <div class="flex flex-col sm:flex-row sm:justify-end mb-4">
                            <a href="{{ route('reports.export.pdf', [
                                'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                                'location_id' => $selectedLocationId ?? request('location_id'),
                            ]) }}"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 
               bg-gradient-to-r from-purple-600 to-purple-700 
               text-white font-semibold rounded-lg shadow-sm 
               hover:from-purple-700 hover:to-purple-800 hover:shadow-md
               transition-all duration-200">

                                {{-- PDF Icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v8m4-4H8m8-6H8a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V6a2 2 0 00-2-2z" />
                                </svg>

                                Download PDF Report
                            </a>
                        </div>

                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        {{-- LEFT: Summary Table --}}
                        <div class="lg:col-span-2 overflow-x-auto">
                            <table class="w-full border border-gray-200 rounded-lg">
                                <tbody>
                                    @foreach ($resultsBreakdown as $scope)
                                        <tr class="bg-gray-50 border-b">
                                            <td class="px-5 py-4 font-semibold text-gray-800">
                                                {{ $scope['name'] }}
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="font-semibold text-gray-900">
                                                    {{ number_format($scope['value'], 2) }}
                                                </span>
                                                <span class="text-sm font-semibold">
                                                    tCO<sub>2</sub>e
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach

                                    <tr class="bg-gray-100">
                                        <td class="px-5 py-4 font-bold text-lg">
                                            Total
                                        </td>
                                        <td class="px-5 py-4 text-right font-bold text-lg">
                                            {{ number_format($total, 2) }}
                                            <span class="text-sm font-semibold">
                                                tCO<sub>2</sub>e
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- RIGHT: Total Offset Card --}}
                        <div
                            class="bg-gradient-to-br from-purple-50 to-white border border-purple-200 rounded-xl p-6 shadow-sm">
                            <p class="text-lg font-semibold mb-2">
                                Total Emissions to Offset
                            </p>

                            <p class="text-3xl font-bold text-purple-700 leading-tight">
                                {{ number_format($total, 2) }}
                            </p>

                            <p class="font-semibold mb-6">
                                tCO<sub>2</sub>e
                            </p>

                            <button
                                class="w-full px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg shadow hover:bg-purple-700 hover:shadow-md transition-all duration-200">
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
