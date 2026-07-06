@extends('layouts.app')

@section('title', 'ESG Scorecard - MenetZero')
@section('page-title', 'ESG Scorecard')

@section('content')
@php
    $years = $scorecard['years'];
    $activeCategory = request('category', 'environment');
    if (!isset($scorecard['categories'][$activeCategory])) {
        $activeCategory = 'environment';
    }
    $currentYear = $fiscalYear;
@endphp
<div class="w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">3-year KPI performance tables</p>
            <h2 class="text-xl font-semibold text-gray-900">{{ $company->name }}</h2>
        </div>
        <form method="GET" action="{{ route('disclosures.esg-scorecard.index') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Reporting year</label>
            <input type="hidden" name="category" value="{{ $activeCategory }}">
            <input type="number" name="fiscal_year" value="{{ $fiscalYear }}" min="2000" max="2100"
                   class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        </form>
    </div>

    <nav class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3 text-sm">
        <a href="{{ route('disclosures.hub', ['fiscal_year' => $fiscalYear]) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">← Disclosures</a>
        <a href="{{ route('disclosures.esg-dashboard', ['fiscal_year' => $fiscalYear]) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">ESG Dashboard</a>
        <a href="{{ route('disclosures.uae-esg.overview', ['fiscal_year' => $fiscalYear]) }}" class="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-50">UAE ESG Report</a>
    </nav>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if(!empty(session('import_errors')))
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <p class="font-medium mb-1">Import notes:</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-6">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="font-semibold text-gray-900">ESG Scorecard</h3>
            <p class="text-sm text-gray-500 mt-1">
                GHG metrics auto-link from Quick Input. Energy, water, waste, and social metrics link from GRI disclosures.
                Manual metrics can be entered below.
                @if($gate->canDisclosureExportType('energy_from_activity', $fiscalYear))
                    <span class="block mt-1 text-brand-700">Enterprise: “Energy from Quick Input” is included in the enterprise scorecard export.</span>
                @endif
            </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('disclosures.esg-scorecard.sync', ['fiscal_year' => $fiscalYear]) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Sync snapshots</button>
                </form>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('esg_scorecard', $fiscalYear)"
                    :href="route('disclosures.esg-scorecard.export', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="btn btn-secondary"
                    locked-class="btn btn-secondary">
                    Export Excel
                </x-plan-gated-link>
                @if($gate->canDisclosureExportType('esg_scorecard_enterprise', $fiscalYear))
                <a href="{{ route('disclosures.esg-scorecard.export-enterprise', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">
                    Export Enterprise (80+ KPIs)
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Bulk import — manual KPIs</h3></div>
        <div class="card-body">
            <p class="text-sm text-gray-500 mb-4">Import manual scorecard metrics (LTIFR overrides, SASB manual fields, community investment) via CSV.</p>
            <div class="flex flex-wrap gap-3 items-end">
                <a href="{{ route('disclosures.esg-scorecard.import-template') }}" class="btn btn-secondary">Download template</a>
                <form method="POST" action="{{ route('disclosures.esg-scorecard.import', ['fiscal_year' => $fiscalYear]) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-center">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" required class="text-sm">
                    <button type="submit" class="btn btn-primary">Import CSV</button>
                </form>
            </div>
        </div>
    </div>

    @if($gate->canDisclosureExportType('hris_kpi_import', $fiscalYear))
    <div class="card mb-6 border-brand-200">
        <div class="card-header"><h3 class="card-title">HRIS / payroll feed (Enterprise)</h3></div>
        <div class="card-body">
            <p class="text-sm text-gray-500 mb-4">
                Bulk import workforce and social KPIs from Workday, SAP SuccessFactors, Oracle HCM, or other HRIS exports.
                Uses metric keys from the enterprise scorecard pack. Imports are audit-logged.
            </p>
            <div class="flex flex-wrap gap-3 items-end">
                <a href="{{ route('disclosures.esg-scorecard.hris-import-template') }}" class="btn btn-secondary">Download HRIS template</a>
                <form method="POST" action="{{ route('disclosures.esg-scorecard.hris-import', ['fiscal_year' => $fiscalYear]) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-center">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" required class="text-sm">
                    <button type="submit" class="btn btn-primary">Import HRIS CSV</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($scorecard['categories'] as $catKey => $category)
            <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear, 'category' => $catKey]) }}"
               class="px-4 py-2 rounded-lg text-sm {{ $activeCategory === $catKey ? 'bg-brand-50 text-brand-700 font-medium border border-brand-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-brand-200' }}">
                {{ $category['title'] }}
            </a>
        @endforeach
    </div>

    @php $category = $scorecard['categories'][$activeCategory]; @endphp

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">{{ $category['title'] }}</h3>
            <p class="card-subtitle">{{ $years[0] }} · {{ $years[1] }} · {{ $years[2] }}</p>
        </div>
        <div class="card-body overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2 pr-4 min-w-[220px]">Metric</th>
                        <th class="py-2 px-2 text-right">{{ $years[0] }}</th>
                        <th class="py-2 px-2 text-right">{{ $years[1] }}</th>
                        <th class="py-2 px-2 text-right">{{ $years[2] }}</th>
                        <th class="py-2 pl-2">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category['rows'] as $row)
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-4">
                                <div>{{ $row['label'] }}</div>
                                <div class="text-xs text-gray-400">{{ $row['unit'] }}</div>
                            </td>
                            @foreach($years as $year)
                                <td class="py-2 px-2 text-right font-mono">
                                    @php $val = $row['values'][$year] ?? null; @endphp
                                    {{ $val !== null ? number_format($val, $row['decimals']) : '—' }}
                                </td>
                            @endforeach
                            <td class="py-2 pl-2 text-xs text-gray-500 uppercase">{{ $row['source'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @php
        $manualRows = collect($category['rows'])->where('editable', true);
    @endphp
    @if($manualRows->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manual metrics — {{ $currentYear }}</h3>
                <p class="card-subtitle">Enter values not available from GHG or GRI modules.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('disclosures.esg-scorecard.update', ['fiscal_year' => $fiscalYear]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                    <input type="hidden" name="metric_year" value="{{ $currentYear }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($manualRows as $row)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $row['label'] }}
                                    <span class="text-gray-400 font-normal">({{ $row['unit'] }})</span>
                                </label>
                                <input type="number" step="any" name="metrics[{{ $row['key'] }}]"
                                       value="{{ old('metrics.'.$row['key'], $row['values'][$currentYear] ?? '') }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary">Save manual metrics</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
