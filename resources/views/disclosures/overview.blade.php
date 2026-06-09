@extends('layouts.app')

@section('title', 'IFRS S2 Disclosures - MenetZero')
@section('page-title', 'IFRS S2 Disclosures')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('disclosures.partials.header', ['framework' => 'ifrs_s2'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">Disclosure completeness</h3>
                <p class="card-subtitle">Track progress across IFRS S2 pillars for {{ $fiscalYear }}.</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-brand-600">{{ $completeness['percent'] }}%</div>
                <div class="text-xs text-gray-500">complete</div>
            </div>
        </div>
        <div class="card-body">
            <div class="w-full bg-gray-100 rounded-full h-3 mb-6">
                <div class="bg-brand-500 h-3 rounded-full transition-all" style="width: {{ $completeness['percent'] }}%"></div>
            </div>

            <div class="space-y-3">
                @foreach($completeness['items'] as $key => $item)
                    @php
                        $routes = [
                            'governance' => route('disclosures.s2.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'governance']),
                            'strategy' => route('disclosures.s2.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'strategy']),
                            'risk_management' => route('disclosures.s2.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'risk_management']),
                            'climate_risks' => route('disclosures.s2.climate-risks.index', ['fiscal_year' => $fiscalYear]),
                            'climate_opportunities' => route('disclosures.s2.climate-opportunities.index', ['fiscal_year' => $fiscalYear]),
                            'reduction_targets' => route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]),
                        ];
                    @endphp
                    <a href="{{ $routes[$key] ?? '#' }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-brand-200 hover:bg-brand-50/30 transition">
                        <div class="flex items-center gap-3">
                            @if($item['complete'])
                                <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">✓</span>
                            @else
                                <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xs">—</span>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ $item['label'] }}</div>
                                @if(isset($item['count']))
                                    <div class="text-xs text-gray-500">{{ $item['count'] }} record(s)</div>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $item['weight'] }}% weight</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h4 class="font-semibold text-gray-900">Ready to export?</h4>
                <p class="text-sm text-gray-500">Preview or download your IFRS S2 climate report for {{ $fiscalYear }}.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('disclosures.s2.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">Preview</a>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear)"
                    :href="route('disclosures.s2.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>
</div>
@endsection
