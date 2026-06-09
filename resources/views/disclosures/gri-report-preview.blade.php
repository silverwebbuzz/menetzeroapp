@extends('layouts.app')

@section('title', 'GRI Report Preview')
@section('page-title', 'GRI Report Preview')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('disclosures.partials.header', ['framework' => 'gri'])

    @if(!$gate->canDisclosureExportType('gri_pdf', $fiscalYear))
        <x-preview-only-banner
            message="GRI preview only on your plan. Upgrade to Growth (AED 2,499/year) to download GRI PDF and content index."
            upgrade-label="Upgrade to Growth" />
    @endif

    <div class="card mb-6">
        <div class="card-body flex flex-col sm:flex-row sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">{{ $report['completeness']['percent'] }}% complete</p>
                <h3 class="text-lg font-semibold">{{ $report['framework'] }}</h3>
            </div>
            <div class="flex gap-2">
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_content_index', $fiscalYear)"
                    :href="route('disclosures.gri.content-index', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="btn btn-secondary"
                    locked-class="btn btn-secondary">
                    Content Index
                </x-plan-gated-link>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_pdf', $fiscalYear)"
                    :href="route('disclosures.gri.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">GRI 305 — Emissions (auto-mapped)</h3></div>
        <div class="card-body text-sm">
            @if($report['gri_305']['has_data'])
                <table class="w-full">
                    <tr><td class="py-1">Scope 1</td><td>{{ number_format($report['gri_305']['scope1_tonnes'], 2) }} tCO₂e</td></tr>
                    <tr><td class="py-1">Scope 2 (location)</td><td>{{ number_format($report['gri_305']['scope2_location_tonnes'], 2) }} tCO₂e</td></tr>
                    <tr><td class="py-1">Scope 3</td><td>{{ number_format($report['gri_305']['scope3_tonnes'], 2) }} tCO₂e</td></tr>
                    <tr class="font-semibold"><td class="py-1">Total</td><td>{{ number_format($report['gri_305']['total_tonnes'], 2) }} tCO₂e</td></tr>
                </table>
            @else
                <p class="text-gray-500">Enter emissions in Quick Input to populate GRI 305.</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">GRI Content Index</h3></div>
        <div class="card-body overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2">Code</th><th>Disclosure</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($report['content_index'] as $row)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 font-mono text-xs">{{ $row['code'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
