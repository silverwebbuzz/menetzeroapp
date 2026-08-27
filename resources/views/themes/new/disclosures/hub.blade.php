{{--
    MENetZero 2.0 - Disclosure hub (Phase 6 body migration).

    Pure navigation: four framework cards with completeness bars, then three
    cross-links. No plan gating and no scripts - verified against the original,
    zero gate calls and zero script blocks.

    SHARED PARTIAL: disclosures.partials.year-select is included UNCHANGED, for
    the reason recorded in section 37.2 - it is self-contained and several
    disclosure pages depend on its year-fallback logic.

    Controller data: $company $fiscalYear $s2Completeness $s1Completeness
    $griCompleteness $uaeEsgCompleteness
    Composer data: $availableYears (read by the partial)
--}}
@extends('layouts.app')

@section('title', 'Disclosures - MenetZero')
@section('page-title', 'Disclosures')

@push('styles')
    <style>
        .dh-card { display: block; color: inherit; text-decoration: none;
            border: 1px solid var(--line); background: var(--surface); padding: 18px 20px; }
        .dh-card:hover { border-color: var(--accent-line); background: var(--canvas-2);
            text-decoration: none; }
        .dh-card__top { display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px; }
        .dh-card__pct { font-size: 21px; font-weight: 600; letter-spacing: -.03em;
            color: var(--accent); line-height: 1; flex-shrink: 0; }
        .dh-card__sub { font-size: 11.5px; color: var(--ink-3); margin: 5px 0 0; }
        .dh-bar { height: 6px; background: var(--line-2); overflow: hidden; margin-top: 14px; }
        .dh-bar span { display: block; height: 100%; background: var(--accent); }
        .dh-grid { display: grid; gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
        .dh-grid--2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="neutral">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">ISSB disclosure workspace</div>
            <h1>{{ $company->name }}</h1>
        </div>
        <div class="mnz-pagehead__actions">
            @include('disclosures.partials.year-select', [
                'action' => route('disclosures.hub'),
            ])
        </div>
    </div>

    <div class="dh-grid">
        <a href="{{ route('disclosures.s2.overview', ['fiscal_year' => $fiscalYear]) }}" class="dh-card" data-pillar="e">
            <div class="dh-card__top">
                <div>
                    <h3 style="font-size:13.5px;font-weight:600;margin:0">IFRS S2 — Climate</h3>
                    <p class="dh-card__sub">Governance, strategy, climate risks, targets &amp; GHG metrics.</p>
                </div>
                <div class="dh-card__pct">{{ $s2Completeness['percent'] }}%</div>
            </div>
            <div class="dh-bar"><span style="width: {{ $s2Completeness['percent'] }}%"></span></div>
        </a>

        <a href="{{ route('disclosures.s1.overview', ['fiscal_year' => $fiscalYear]) }}" class="dh-card" data-pillar="g">
            <div class="dh-card__top">
                <div>
                    <h3 style="font-size:13.5px;font-weight:600;margin:0">IFRS S1 — Sustainability</h3>
                    <p class="dh-card__sub">Material topics, broader sustainability risks &amp; governance.</p>
                </div>
                <div class="dh-card__pct">{{ $s1Completeness['percent'] }}%</div>
            </div>
            <div class="dh-bar"><span style="width: {{ $s1Completeness['percent'] }}%"></span></div>
        </a>

        <a href="{{ route('disclosures.gri.overview', ['fiscal_year' => $fiscalYear]) }}" class="dh-card" data-pillar="s">
            <div class="dh-card__top">
                <div>
                    <h3 style="font-size:13.5px;font-weight:600;margin:0">GRI — Sustainability</h3>
                    <p class="dh-card__sub">Material topics, E/S/G metrics, content index &amp; report.</p>
                </div>
                <div class="dh-card__pct">{{ $griCompleteness['percent'] }}%</div>
            </div>
            <div class="dh-bar"><span style="width: {{ $griCompleteness['percent'] }}%"></span></div>
        </a>

        <a href="{{ route('disclosures.uae-esg.overview', ['fiscal_year' => $fiscalYear]) }}" class="dh-card" style="border-color:var(--accent-line)">
            <div class="dh-card__top">
                <div>
                    <h3 style="font-size:13.5px;font-weight:600;margin:0">UAE ESG Report</h3>
                    <p class="dh-card__sub">Unified report — narrative chapters, GHG, IFRS/GRI indexes &amp; SDG mapping.</p>
                </div>
                <div class="dh-card__pct">{{ $uaeEsgCompleteness['percent'] }}%</div>
            </div>
            <div class="dh-bar"><span style="width: {{ $uaeEsgCompleteness['percent'] }}%"></span></div>
        </a>
    </div>

    <div class="dh-grid dh-grid--2">
        <a href="{{ route('disclosures.esg-dashboard', ['fiscal_year' => $fiscalYear]) }}" class="dh-card">
            <h3 style="font-size:13.5px;font-weight:600;margin:0">ESG Dashboard</h3>
            <p class="dh-card__sub">Environmental, social, and governance scorecards across all frameworks.</p>
        </a>
        <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear]) }}" class="dh-card">
            <h3 style="font-size:13.5px;font-weight:600;margin:0">ESG Scorecard</h3>
            <p class="dh-card__sub">3-year KPI tables — GHG, GRI metrics, and manual social/governance data.</p>
        </a>
        <a href="{{ route('disclosures.esg-depth.overview', ['fiscal_year' => $fiscalYear]) }}" class="dh-card">
            <h3 style="font-size:13.5px;font-weight:600;margin:0">ESG Depth</h3>
            <p class="dh-card__sub">Stakeholders, materiality matrix, supply chain, and non-climate targets.</p>
        </a>
        <div class="mnz-panel">
            <div class="mnz-panel__body" style="font-size:12.5px;color:var(--ink-2)">
                <p style="margin:0">IFRS S1 report can include the S2 climate appendix. GRI 305 emissions auto-map from your GHG inventory.</p>
            </div>
        </div>
    </div>
</div>
@endsection
