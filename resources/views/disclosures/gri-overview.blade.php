@extends('layouts.app')

@section('title', 'GRI Disclosures - MenetZero')
@section('page-title', 'GRI Sustainability Reporting')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('disclosures.partials.header', ['framework' => 'gri'])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">GRI completeness</h3>
                <p class="card-subtitle">Progress for {{ $fiscalYear }} reporting cycle.</p>
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
                            'material_topics' => route('disclosures.gri.material-topics', ['fiscal_year' => $fiscalYear]),
                            'material_topics_process' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'material_topics_process']),
                            'general' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'general']),
                            'energy' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'energy']),
                            'water' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'water']),
                            'waste' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'waste']),
                            'social_hr' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'social_hr']),
                            'diversity' => route('disclosures.gri.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'diversity']),
                            'gri_305' => route('reports.index'),
                        ];
                    @endphp
                    <a href="{{ $routes[$key] ?? '#' }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-brand-200">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $item['complete'] ? 'bg-green-100 text-green-700 font-bold' : 'bg-gray-100 text-gray-400' }}">{{ $item['complete'] ? '✓' : '—' }}</span>
                            <div>
                                <div class="font-medium">{{ $item['label'] }}</div>
                                @if(isset($item['count']))<div class="text-xs text-gray-500">{{ $item['count'] }} topic(s)</div>@endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $item['weight'] }}%</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h4 class="font-semibold">Exports</h4>
                <p class="text-sm text-gray-500">GRI report PDF and content index for stakeholders.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('disclosures.gri.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary">Preview</a>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('gri_content_index', $fiscalYear)"
                    :href="route('disclosures.gri.content-index', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="btn btn-secondary"
                    locked-class="btn btn-secondary">
                    Content Index CSV
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
</div>
@endsection
