{{--
    MENetZero 2.0 - UAE ESG report overview (Phase 6 body migration).

    FOUR GATE CALLS, THREE DIFFERENT GATING SHAPES - all preserved exactly:

      assurance_upload        outer conditional hides the WHOLE assurance card
      uae_esg_pdf             x-plan-gated-link (locked state shown)
      uae_esg_pdf_enterprise  outer conditional hides the button entirely

    The two conditional gates are NOT x-plan-gated-link and must not be
    "upgraded" into one: today a tier without the entitlement sees nothing at
    all, whereas a gated link would show a locked button. That is a deliberate
    difference in what those tiers are told exists.

    ASSURANCE BLOCK: a multipart upload plus a delete form. Preserved with
    enctype, both csrf tokens, method DELETE and the confirm() text. Dropping
    the enctype would break the upload silently - the form would still submit,
    just without the file (same failure mode as the profile logo, section 34.3).

    EIGHT route-map keys. Note materiality links into the GRI section editor and
    ghg_inventory links to reports.index - both cross-framework by design.

    SHARED PARTIAL: disclosures.partials.header included UNCHANGED (section 38.2).

    Controller data: $company $fiscalYear $completeness $sectionConfig
    $assuranceDocument
    Composer data: $gate (PlanGateComposer), $availableYears (read by the partial)
--}}
@extends('layouts.app')

@section('title', 'UAE ESG Report - MenetZero')
@section('page-title', 'UAE ESG Report')

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
        .ue-sections { display: grid; gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .ue-section { display: block; color: inherit; text-decoration: none;
            border: 1px solid var(--line); background: var(--surface); padding: 16px 18px; }
        .ue-section:hover { border-color: var(--accent-line); background: var(--canvas-2);
            text-decoration: none; }
        .ue-file { display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
            padding: 12px 14px; background: var(--canvas); border: 1px solid var(--line);
            margin-bottom: 16px; font-size: 12.5px; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="neutral">

    @include('disclosures.partials.header', ['framework' => 'esg_report'])

    @if(session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3 style="font-size:14px;font-weight:600;margin:0">Report completeness</h3>
                <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">Progress for {{ $fiscalYear }} — narrative + linked GHG / IFRS / GRI data.</p>
            </div>
            <div style="font-size:26px;font-weight:600;letter-spacing:-.03em;color:var(--accent);line-height:1">{{ $completeness['percent'] }}%</div>
        </div>
        <div class="mnz-panel__body">
            <div class="dz-bar"><span style="width: {{ $completeness['percent'] }}%"></span></div>

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
                <a href="{{ $routes[$key] ?? '#' }}" class="dz-item">
                    <div style="display:flex;align-items:center;gap:11px">
                        <span class="dz-tick {{ $item['complete'] ? 'dz-tick--on' : 'dz-tick--off' }}">{{ $item['complete'] ? '✓' : '—' }}</span>
                        <div style="font-weight:500;font-size:12.5px">{{ $item['label'] }}</div>
                    </div>
                    <span style="font-size:11px;color:var(--ink-4)">{{ $item['weight'] }}%</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Narrative sections --}}
    <div class="ue-sections">
        @foreach($sectionConfig as $key => $section)
            <a href="{{ route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => $key]) }}" class="ue-section">
                <h4 style="font-size:13px;font-weight:600;margin:0">{{ $section['title'] }}</h4>
                <p style="font-size:12px;color:var(--ink-3);margin:5px 0 0">{{ $section['description'] ?? 'Narrative section' }}</p>
            </a>
        @endforeach
    </div>

    {{-- Assurance upload. The outer gate HIDES this whole card for tiers without
         the entitlement - it is not a locked-state link. Do not convert it. --}}
    @if($gate->canDisclosureExportType('assurance_upload', $fiscalYear))
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Independent assurance statement (Enterprise)</h3>
        </div>
        <div class="mnz-panel__body">
            <p style="font-size:12.5px;color:var(--ink-3);margin:0 0 16px">
                Upload the verifier’s signed assurance PDF (e.g. LRQA). Narrative assurance status remains in
                <a href="{{ route('disclosures.uae-esg.sections.edit', ['fiscal_year' => $fiscalYear, 'section' => 'about_report']) }}">About This Report</a>.
                Growth plans use text fields only.
            </p>

            @if(!empty($assuranceDocument))
                <div class="ue-file">
                    <div>
                        <span style="font-weight:500">{{ $assuranceDocument['filename'] }}</span>
                        @if(!empty($assuranceDocument['uploaded_at']))
                            <span style="color:var(--ink-3)"> — uploaded {{ \Carbon\Carbon::parse($assuranceDocument['uploaded_at'])->format('d M Y') }}</span>
                        @endif
                    </div>
                    <a href="{{ route('disclosures.uae-esg.assurance.download', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">Download</a>
                    <form method="POST" action="{{ route('disclosures.uae-esg.assurance.delete', ['fiscal_year' => $fiscalYear]) }}" onsubmit="return confirm('Remove assurance PDF?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mnz-btn" style="border-color:var(--bad-line);color:var(--bad)">Remove</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('disclosures.uae-esg.assurance.upload', ['fiscal_year' => $fiscalYear]) }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
                @csrf
                <input type="file" name="file" accept="application/pdf,.pdf" required style="font-size:12.5px">
                <button type="submit" class="mnz-btn mnz-btn--primary">{{ !empty($assuranceDocument) ? 'Replace PDF' : 'Upload PDF' }}</button>
            </form>
        </div>
    </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <div style="min-width:min(100%,300px)">
                <h4 style="font-size:13px;font-weight:600;margin:0">Unified UAE ESG Report</h4>
                <p style="font-size:12.5px;color:var(--ink-3);margin:4px 0 0">
                    Combines narrative chapters with auto-linked GHG inventory, IFRS S2 climate,
                    GRI metrics, and disclosure indexes.
                </p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <a href="{{ route('disclosures.uae-esg.report.preview', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">Preview</a>
                <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">ESG Scorecard</a>

                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('uae_esg_pdf', $fiscalYear)"
                    :href="route('disclosures.uae-esg.report.pdf', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn mnz-btn--primary"
                    locked-class="mnz-btn">
                    Download PDF
                </x-plan-gated-link>

                {{-- Hidden entirely below Enterprise - not a locked link. --}}
                @if($gate->canDisclosureExportType('uae_esg_pdf_enterprise', $fiscalYear))
                <a href="{{ route('disclosures.uae-esg.report.pdf-enterprise', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn mnz-btn--accent">
                    Download Enterprise PDF
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
