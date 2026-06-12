@extends('layouts.app')

@section('title', 'IFRS S2 Report Preview')
@section('page-title', 'IFRS S2 Report Preview')

@section('content')
<div class="w-full">
    @include('disclosures.partials.header', ['framework' => 'ifrs_s2'])

    <x-managed-client-year-banner :fiscal-year="$fiscalYear" export-code="ifrs_s2_pdf" />

    <x-export-readiness-banner :readiness="$dataReadiness ?? null" />

    <div class="card mb-6 {{ !$gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear) ? 'relative' : '' }}">
        @if(!$gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear))
            <div class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center overflow-hidden rounded-lg" aria-hidden="true">
                <span class="text-4xl font-bold uppercase tracking-widest text-slate-200/80 -rotate-12 select-none">Preview</span>
            </div>
        @endif
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Completeness: {{ $report['completeness']['percent'] }}%</p>
                <h3 class="text-lg font-semibold text-gray-900">{{ $report['framework'] }}</h3>
                <p class="text-sm text-gray-600">Fiscal year {{ $fiscalYear }} · Generated {{ $report['generated_at'] }}</p>
            </div>
            <x-plan-gated-link
                :allowed="$gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear)"
                :href="route('disclosures.s2.report.pdf', ['fiscal_year' => $fiscalYear])"
                :message="$gate->disclosureExportMessage()">
                Download PDF
            </x-plan-gated-link>
        </div>
    </div>

    @foreach(['governance' => 'Climate Governance', 'strategy' => 'Climate Strategy', 'risk_management' => 'Risk Management'] as $key => $title)
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
            <div class="card-body text-sm text-gray-700 space-y-2">
                @php $section = $report[$key] ?? []; $fields = $report['section_config'][$key]['fields'] ?? []; @endphp
                @forelse($fields as $fieldKey => $field)
                    @if(!empty($section[$fieldKey]))
                        <div>
                            <div class="font-medium text-gray-900">{{ $field['label'] }}</div>
                            <div class="whitespace-pre-wrap">{{ $section[$fieldKey] }}</div>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500">No content.</p>
                @endforelse
            </div>
        </div>
    @endforeach

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">GHG Emissions (IFRS S2 §29)</h3></div>
        <div class="card-body">
            @if($report['ghg']['has_data'])
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">Scope</th><th>tCO₂e</th></tr></thead>
                    <tbody>
                        @foreach($report['ghg']['scope_tonnes'] as $scope => $tonnes)
                            <tr class="border-b border-gray-100"><td class="py-2">{{ $scope }}</td><td>{{ number_format($tonnes, 2) }}</td></tr>
                        @endforeach
                        <tr class="font-semibold"><td class="py-2">Total</td><td>{{ number_format($report['ghg']['total_tonnes'], 2) }}</td></tr>
                    </tbody>
                </table>
                <p class="text-xs text-gray-500 mt-3">Scope 2 location-based: {{ number_format($report['ghg']['scope2_location_tonnes'], 2) }} tCO₂e · Market-based: {{ number_format($report['ghg']['scope2_market_tonnes'], 2) }} tCO₂e</p>
            @else
                <p class="text-gray-500 text-sm">No emission data for {{ $fiscalYear }}. Enter data in Quick Input first.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Climate risks ({{ $report['climate_risks']->count() }})</h3></div>
            <div class="card-body text-sm space-y-2">
                @forelse($report['climate_risks'] as $risk)
                    <div><strong>{{ $risk->name }}</strong> — {{ ucfirst($risk->risk_type) }}</div>
                @empty
                    <p class="text-gray-500">None registered.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Reduction targets ({{ $report['reduction_targets']->count() }})</h3></div>
            <div class="card-body text-sm space-y-2">
                @forelse($report['reduction_targets'] as $target)
                    <div><strong>{{ $target->name }}</strong> — {{ $target->target_year }}</div>
                @empty
                    <p class="text-gray-500">None defined.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
