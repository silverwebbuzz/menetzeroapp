{{--
    MENetZero 2.0 - IFRS S2 Disclosures (Phase 6 body migration).

    PLAN GATING preserved: the PDF export is wrapped in x-plan-gated-link with
    $gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear). This is a paid
    disclosure export - rendering it as a bare link would hand it to every tier
    (risk R-1, same shape as the SASB export in section 25 and the Reports
    exports in section 26.6). The Preview link beside it is deliberately
    ungated, exactly as in the original.

    SHARED PARTIAL: disclosures.partials.header is included UNCHANGED. It owns
    the framework label, the reporting-year form and the disclosure sub-nav, and
    is self-contained. It styles itself with inline Tailwind, which the new
    shell still loads, so it works correctly while keeping the old look.
    Forking it per theme would duplicate the year-fallback logic that every
    disclosure overview depends on.

    Controller data: $company $fiscalYear $completeness
    Composer data: $gate (PlanGateComposer), $availableYears (read by the partial)
--}}
@extends('layouts.app')

@section('title', 'IFRS S2 Disclosures - MenetZero')
@section('page-title', 'IFRS S2 Disclosures')

@push('styles')
    <style>
        .dz-bar { height: 8px; background: var(--line-2); overflow: hidden; margin-bottom: 20px; }
        .dz-bar span { display: block; height: 100%; background: var(--accent); }
        .dz-item { display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 11px 14px; border: 1px solid var(--line);
            color: inherit; text-decoration: none; }
        .dz-item + .dz-item { margin-top: 8px; }
        .dz-item:hover { background: var(--canvas-2); border-color: var(--accent-line);
            text-decoration: none; }
        .dz-tick { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 11px; }
        .dz-tick--on { background: var(--ok-tint); color: var(--ok); font-weight: 700; }
        .dz-tick--off { background: var(--canvas-2); color: var(--ink-4); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="e">

    @include('disclosures.partials.header', ['framework' => 'ifrs_s2'])

    @if(session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3 style="font-size:14px;font-weight:600;margin:0">Disclosure completeness</h3>
                <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">Track progress across IFRS S2 pillars for {{ $fiscalYear }}.</p>
            </div>
            <div style="text-align:right">
                <div style="font-size:26px;font-weight:600;letter-spacing:-.03em;color:var(--accent);line-height:1">{{ $completeness['percent'] }}%</div>
                <div style="font-size:11px;color:var(--ink-3);margin-top:3px">complete</div>
            </div>
        </div>
        <div class="mnz-panel__body">
            <div class="dz-bar"><span style="width: {{ $completeness['percent'] }}%"></span></div>

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
                <a href="{{ $routes[$key] ?? '#' }}" class="dz-item">
                    <div style="display:flex;align-items:center;gap:11px">
                        <span class="dz-tick {{ $item['complete'] ? 'dz-tick--on' : 'dz-tick--off' }}">{{ $item['complete'] ? '✓' : '—' }}</span>
                        <div>
                            <div style="font-weight:500;font-size:12.5px">{{ $item['label'] }}</div>
                            @if(isset($item['count']))
                                <div style="font-size:11px;color:var(--ink-3);margin-top:2px">{{ $item['count'] }} record(s)</div>
                            @endif
                        </div>
                    </div>
                    <span style="font-size:11px;color:var(--ink-4)">{{ $item['weight'] }}% weight</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <div style="min-width:min(100%,300px)">
                <h4 style="font-size:13px;font-weight:600;margin:0">Ready to export?</h4>
                <p style="font-size:12.5px;color:var(--ink-3);margin:4px 0 0">Preview or download your IFRS S2 climate report for {{ $fiscalYear }}.</p>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('disclosures.s2.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">Preview</a>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('ifrs_s2_pdf', $fiscalYear)"
                    :href="route('disclosures.s2.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn mnz-btn--primary"
                    locked-class="mnz-btn">
                    Download PDF
                </x-plan-gated-link>
            </div>
        </div>
    </div>
</div>
@endsection
