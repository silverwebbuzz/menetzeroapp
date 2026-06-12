@extends('layouts.app')

@section('title', 'IFRS S1 Report Preview')
@section('page-title', 'IFRS S1 Report Preview')

@section('content')
<div class="w-full">
    @include('disclosures.partials.header', ['framework' => 'ifrs_s1'])

    <x-managed-client-year-banner :fiscal-year="$fiscalYear" export-code="ifrs_s1_pdf" />

    <div class="card mb-6">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Completeness: {{ $report['completeness']['percent'] }}%</p>
                <h3 class="text-lg font-semibold">{{ $report['framework'] }}</h3>
                @if($report['include_s2'])
                    <p class="text-sm text-brand-700">IFRS S2 climate appendix will be included.</p>
                @endif
            </div>
            <x-plan-gated-link
                :allowed="$gate->canDisclosureExportType('ifrs_s1_pdf', $fiscalYear)"
                :href="route('disclosures.s1.report.pdf', ['fiscal_year' => $fiscalYear, 'include_s2' => $includeS2 ? 1 : 0])"
                :message="$gate->disclosureExportMessage()">
                Download PDF
            </x-plan-gated-link>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Material topics</h3></div>
        <div class="card-body text-sm">
            @forelse($report['material_topics'] as $topic)
                <div class="mb-2"><strong>{{ $topic['label'] }}</strong> — {{ $topic['rationale'] ?: 'Material' }}</div>
            @empty
                <p class="text-gray-500">No material topics selected.</p>
            @endforelse
        </div>
    </div>

    @foreach(['governance' => 'Governance', 'strategy' => 'Strategy', 'risk_management' => 'Risk Management'] as $key => $title)
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
            <div class="card-body text-sm text-gray-700">
                @php $section = $report[$key] ?? []; @endphp
                @forelse($report['section_config'][$key]['fields'] ?? [] as $fieldKey => $field)
                    @if(!empty($section[$fieldKey]))
                        <div class="mb-2"><strong>{{ $field['label'] }}</strong><div class="whitespace-pre-wrap">{{ $section[$fieldKey] }}</div></div>
                    @endif
                @empty
                @endforelse
            </div>
        </div>
    @endforeach

    @if($report['include_s2'] && $report['s2_report'])
        <div class="card">
            <div class="card-header"><h3 class="card-title">IFRS S2 Climate Appendix (summary)</h3></div>
            <div class="card-body text-sm">
                <p>Total GHG: {{ number_format($report['s2_report']['ghg']['total_tonnes'] ?? 0, 2) }} tCO₂e</p>
                <p>Climate risks: {{ $report['s2_report']['climate_risks']->count() }}</p>
            </div>
        </div>
    @endif
</div>
@endsection
