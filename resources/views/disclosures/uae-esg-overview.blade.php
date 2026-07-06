@extends('layouts.app')

@section('title', 'UAE ESG Report - MenetZero')
@section('page-title', 'UAE ESG Report')

@section('content')
<div class="w-full">
    @include('disclosures.partials.header', ['framework' => 'esg_report'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">Report completeness</h3>
                <p class="card-subtitle">Progress for {{ $fiscalYear }} — narrative + linked GHG / IFRS / GRI data.</p>
            </div>
            <div class="text-3xl font-bold text-brand-600">{{ $completeness['percent'] }}%</div>
        </div>
        <div class="card-body">
            <div class="w-full bg-gray-100 rounded-full h-3 mb-6">
                <div class="bg-brand-500 h-3 rounded-full" style="width: {{ $completeness['percent'] }}%"></div>
            </div>
            <div class="space-y-3">
                @foreach($completeness['items'] as $key => $item)
                    @php
                        $routes = [
                            'about_report' => route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'about_report']),
                            'leadership_message' => route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'leadership_message']),
                            'about_company' => route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'about_company']),
                            'esg_strategy' => route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'esg_strategy']),
                            'materiality' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'material_topics_process']),
                            'ghg_inventory' => route('reports.index'),
                            'ifrs_s2_climate' => route('disclosures.s2.overview', ['fiscal_year' => $fiscalYear]),
                            'gri_index' => route('disclosures.gri.overview', ['fiscal_year' => $fiscalYear]),
                        ];
                    @endphp
                    <a href="{{ $routes[$key] ?? '#' }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-brand-200">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $item['complete'] ? 'bg-green-100 text-green-700 font-bold' : 'bg-gray-100 text-gray-400' }}">{{ $item['complete'] ? '✓' : '—' }}</span>
                            <div class="font-medium">{{ $item['label'] }}</div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $item['weight'] }}%</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @foreach($sectionConfig as $key => $section)
            <a href="{{ route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => $key]) }}" class="card hover:border-brand-300 transition block">
                <div class="card-body">
                    <h4 class="font-semibold text-gray-900">{{ $section['title'] }}</h4>
                    <p class="text-sm text-gray-500 mt-1">{{ $section['description'] ?? 'Narrative section' }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h4 class="font-semibold">Unified UAE ESG Report</h4>
                <p class="text-sm text-gray-500">Combines narrative chapters with auto-linked GHG inventory, IFRS S2 climate, GRI metrics, and disclosure indexes.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('disclosures.uae-esg.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">Preview</a>
                <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">ESG Scorecard</a>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('uae_esg_pdf', $fiscalYear)"
                    :href="route('disclosures.uae-esg.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>
</div>
@endsection
