@extends('layouts.app')

@section('title', 'UAE ESG Report Preview')
@section('page-title', 'UAE ESG Report Preview')

@section('content')
<div class="w-full">
    @include('disclosures.partials.header', ['framework' => 'esg_report'])

    <x-managed-client-year-banner :fiscal-year="$fiscalYear" export-code="uae_esg_pdf" />

    <x-export-readiness-banner :readiness="$dataReadiness ?? null" />

    <div class="card mb-6">
        <div class="card-body flex flex-col sm:flex-row sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">{{ $report['completeness']['percent'] }}% complete</p>
                <h3 class="text-lg font-semibold">{{ $report['framework'] }}</h3>
                <p class="text-sm text-gray-500 mt-1">FY {{ $fiscalYear }} · Generated {{ $report['generated_at'] }}</p>
            </div>
            <div class="flex gap-2">
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('uae_esg_pdf', $fiscalYear)"
                    :href="route('disclosures.uae-esg.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>

    @foreach($report['narrative'] as $key => $section)
        @php $content = $section['content'] ?? []; $fields = $report['section_config'][$key]['fields'] ?? []; @endphp
        @if(!empty(array_filter($content ?? [])))
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ $section['title'] }}</h3></div>
                <div class="card-body text-sm space-y-2">
                    @foreach($fields as $fieldKey => $field)
                        @if(isset($content[$fieldKey]) && $content[$fieldKey] !== '')
                            <div>
                                <div class="text-xs text-gray-500 uppercase">{{ $field['label'] }}</div>
                                <div class="whitespace-pre-wrap">{{ $content[$fieldKey] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">GHG Inventory</h3></div>
        <div class="card-body text-sm">
            @if($report['ghg']['has_data'])
                <table class="w-full">
                    <tr><td class="py-1">Scope 1</td><td>{{ number_format($report['ghg']['scope_tonnes']['Scope 1'], 4) }} tCO₂e</td></tr>
                    <tr><td class="py-1">Scope 2 (location)</td><td>{{ number_format($report['ghg']['scope2_location_tonnes'], 4) }} tCO₂e</td></tr>
                    <tr><td class="py-1">Scope 2 (market)</td><td>{{ number_format($report['ghg']['scope2_market_tonnes'], 4) }} tCO₂e</td></tr>
                    <tr><td class="py-1">Scope 3</td><td>{{ number_format($report['ghg']['scope_tonnes']['Scope 3'], 2) }} tCO₂e</td></tr>
                    <tr class="font-semibold"><td class="py-1">Total</td><td>{{ number_format($report['ghg']['total_tonnes'], 2) }} tCO₂e</td></tr>
                </table>
            @else
                <p class="text-gray-500">Enter emissions in Quick Input to populate the GHG inventory section.</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">IFRS S2 Disclosure Index</h3></div>
        <div class="card-body overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">Paragraph</th><th>Topic</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($report['ifrs_s2_index'] as $row)
                        <tr class="border-b border-gray-50"><td class="py-2">{{ $row['paragraph'] }}</td><td>{{ $row['topic'] }}</td><td>{{ $row['status'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">UN SDG Mapping</h3></div>
        <div class="card-body overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">Topic</th><th>SDG Goals</th><th>Material</th></tr></thead>
                <tbody>
                    @foreach($report['sdg_map'] as $row)
                        <tr class="border-b border-gray-50">
                            <td class="py-2">{{ ucfirst(str_replace('_', ' ', $row['topic_key'])) }}</td>
                            <td>{{ implode(', ', $row['sdg_goals']) }} — {{ $row['sdg_label'] }}</td>
                            <td>{{ $row['material'] ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-xs text-gray-500">{{ $report['disclaimer'] }}</div>
    </div>
</div>
@endsection
