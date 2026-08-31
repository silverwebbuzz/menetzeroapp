{{--
    MENetZero 2.0 - ESG scorecard (Phase 6 body migration).

    FOUR GATE CALLS, and again the SHAPES differ - preserved exactly:

      esg_scorecard             x-plan-gated-link       locked state shown
      esg_scorecard_enterprise  outer conditional       button hidden entirely
      hris_kpi_import           outer conditional       WHOLE card hidden
      energy_from_activity      outer conditional       one explanatory line hidden

    Merging any conditional into a gated link would show a locked control where
    today the tier sees nothing (section 39.1).

    FOUR POST FORMS, all with csrf; TWO carry enctype:
      sync         disclosures.esg-scorecard.sync
      import       disclosures.esg-scorecard.import        multipart
      hris-import  disclosures.esg-scorecard.hris-import   multipart
      update       disclosures.esg-scorecard.update
    Dropping an enctype breaks that upload silently (section 34.3).

    DYNAMIC FIELD NAMES: the manual-metrics inputs post as
    metrics[<row key>] and repopulate from old('metrics.<key>'). Both halves
    must stay in step or a validation bounce loses the user's edits.

    x-field-help key="scorecard.manual_intro" is preserved - these are the
    invisible-in-a-screenshot losses caught in section 20.

    YEAR PICKER REMOVED: this page used disclosures.partials.year-select,
    carrying 'hidden' => ['category' => ...] so the active tab survived a year
    change. The topbar switcher redirects back(), which preserves the whole
    URL including ?category=, so the tab still survives.

    Controller data: $company $fiscalYear $scorecard
    Composer data: $gate (PlanGateComposer), $availableYears (read by the partial)
--}}
@extends('layouts.app')

@section('title', 'ESG Scorecard - MenetZero')
@section('page-title', 'ESG Scorecard')

@push('styles')
    <style>
        .sc-subnav { display: flex; flex-wrap: wrap; gap: 8px;
            border-bottom: 1px solid var(--line); padding-bottom: 12px; }
        .sc-subnav a { font-size: 12.5px; padding: 6px 11px; color: var(--ink-2);
            text-decoration: none; }
        .sc-subnav a:hover { background: var(--canvas-2); text-decoration: none; }
        .sc-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .sc-tab { font-size: 12.5px; padding: 7px 14px; border: 1px solid var(--line);
            color: var(--ink-2); text-decoration: none; background: var(--surface); }
        .sc-tab:hover { border-color: var(--accent-line); text-decoration: none; }
        .sc-tab.is-active { background: var(--accent-tint); border-color: var(--accent-line);
            color: var(--accent); font-weight: 500; }
        .sc-fields { display: grid; gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        table.mnz-table td.t-r, table.mnz-table th.t-r { text-align: right; }
        table.mnz-table td.t-num { font-family: var(--mono); text-align: right; }
        table.mnz-table td.t-src { font-size: 11px; color: var(--ink-3);
            text-transform: uppercase; letter-spacing: .04em; }
    </style>
@endpush

@section('content')
@php
    $years = $scorecard['years'];
    $activeCategory = request('category', 'environment');
    if (!isset($scorecard['categories'][$activeCategory])) {
        $activeCategory = 'environment';
    }
    $currentYear = $fiscalYear;
@endphp

<div class="mnz-stack" data-pillar="neutral">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">3-year KPI performance tables</div>
            <h1>{{ $company->name }}</h1>
        </div>
    </div>

    <nav class="sc-subnav">
        <a href="{{ route('disclosures.hub', ['fiscal_year' => $fiscalYear]) }}">← Disclosures</a>
        <a href="{{ route('disclosures.esg-dashboard', ['fiscal_year' => $fiscalYear]) }}">ESG Dashboard</a>
        <a href="{{ route('disclosures.uae-esg.overview', ['fiscal_year' => $fiscalYear]) }}">UAE ESG Report</a>
    </nav>

    @if(session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok);font-size:12.5px">{{ session('success') }}</div>
        </div>
    @endif

    @if(!empty(session('import_errors')))
        <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint)">
            <div class="mnz-panel__body" style="color:var(--warn);font-size:12.5px">
                <p style="margin:0 0 6px;font-weight:600">Import notes:</p>
                <ul style="margin:0;padding-left:18px">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Header actions --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body" style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <div style="min-width:min(100%,320px)">
                <h3 style="font-size:13px;font-weight:600;margin:0">ESG Scorecard</h3>
                <p style="font-size:12.5px;color:var(--ink-3);margin:5px 0 0">
                    GHG metrics auto-link from Quick Input. Energy, water, waste, and social metrics
                    link from GRI disclosures. Manual metrics can be entered below.
                    @if($gate->canDisclosureExportType('energy_from_activity', $fiscalYear))
                        <span style="display:block;margin-top:5px;color:var(--accent)">Enterprise: “Energy from Quick Input” is included in the enterprise scorecard export.</span>
                    @endif
                </p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <form method="POST" action="{{ route('disclosures.esg-scorecard.sync', ['fiscal_year' => $fiscalYear]) }}">
                    @csrf
                    <button type="submit" class="mnz-btn">Sync snapshots</button>
                </form>

                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('esg_scorecard', $fiscalYear)"
                    :href="route('disclosures.esg-scorecard.export', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn"
                    locked-class="mnz-btn">
                    Export Excel
                </x-plan-gated-link>

                {{-- Hidden entirely below Enterprise - not a locked link. --}}
                @if($gate->canDisclosureExportType('esg_scorecard_enterprise', $fiscalYear))
                <a href="{{ route('disclosures.esg-scorecard.export-enterprise', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">
                    Export Enterprise (80+ KPIs)
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Bulk import --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Bulk import — manual KPIs</h3>
        </div>
        <div class="mnz-panel__body">
            <p style="font-size:12.5px;color:var(--ink-3);margin:0 0 16px">
                Import manual scorecard metrics (LTIFR overrides, SASB manual fields,
                community investment) via CSV.
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
                <a href="{{ route('disclosures.esg-scorecard.import-template') }}" class="mnz-btn">Download template</a>
                <form method="POST" action="{{ route('disclosures.esg-scorecard.import', ['fiscal_year' => $fiscalYear]) }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" required style="font-size:12.5px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Import CSV</button>
                </form>
            </div>
        </div>
    </div>

    {{-- HRIS feed. The gate hides this WHOLE card below Enterprise. --}}
    @if($gate->canDisclosureExportType('hris_kpi_import', $fiscalYear))
    <div class="mnz-panel" style="border-color:var(--accent-line)">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">HRIS / payroll feed (Enterprise)</h3>
        </div>
        <div class="mnz-panel__body">
            <p style="font-size:12.5px;color:var(--ink-3);margin:0 0 16px">
                Bulk import workforce and social KPIs from Workday, SAP SuccessFactors, Oracle HCM,
                or other HRIS exports. Uses metric keys from the enterprise scorecard pack.
                Imports are audit-logged.
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
                <a href="{{ route('disclosures.esg-scorecard.hris-import-template') }}" class="mnz-btn">Download HRIS template</a>
                <form method="POST" action="{{ route('disclosures.esg-scorecard.hris-import', ['fiscal_year' => $fiscalYear]) }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" required style="font-size:12.5px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Import HRIS CSV</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Category tabs --}}
    <div class="sc-tabs">
        @foreach($scorecard['categories'] as $catKey => $category)
            <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear, 'category' => $catKey]) }}"
               class="sc-tab {{ $activeCategory === $catKey ? 'is-active' : '' }}">
                {{ $category['title'] }}
            </a>
        @endforeach
    </div>

    @php $category = $scorecard['categories'][$activeCategory]; @endphp

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3 style="font-size:14px;font-weight:600;margin:0">{{ $category['title'] }}</h3>
                <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">{{ $years[0] }} · {{ $years[1] }} · {{ $years[2] }}</p>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table class="mnz-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="min-width:200px">Metric</th>
                        <th class="t-r">{{ $years[0] }}</th>
                        <th class="t-r">{{ $years[1] }}</th>
                        <th class="t-r">{{ $years[2] }}</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category['rows'] as $row)
                        <tr>
                            <td>
                                <div>{{ $row['label'] }}</div>
                                <div style="font-size:11px;color:var(--ink-4);margin-top:2px">{{ $row['unit'] }}</div>
                            </td>
                            @foreach($years as $year)
                                <td class="t-num">
                                    @php $val = $row['values'][$year] ?? null; @endphp
                                    {{ $val !== null ? number_format($val, $row['decimals']) : '—' }}
                                </td>
                            @endforeach
                            <td class="t-src">{{ $row['source'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Manual metrics --}}
    @php
        $manualRows = collect($category['rows'])->where('editable', true);
    @endphp
    @if($manualRows->isNotEmpty())
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3 style="font-size:14px;font-weight:600;margin:0">Manual metrics — {{ $currentYear }}</h3>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">Enter values not available from GHG or GRI modules.</p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <x-field-help key="scorecard.manual_intro" class="mb-4" />

                <form method="POST" action="{{ route('disclosures.esg-scorecard.update', ['fiscal_year' => $fiscalYear]) }}">
                    @csrf
                    <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                    <input type="hidden" name="metric_year" value="{{ $currentYear }}">

                    <div class="sc-fields">
                        @foreach($manualRows as $row)
                            <div class="mnz-field">
                                <label class="mnz-label">
                                    {{ $row['label'] }}
                                    <span style="color:var(--ink-4);font-weight:400">({{ $row['unit'] }})</span>
                                </label>
                                <input type="number" step="any" name="metrics[{{ $row['key'] }}]"
                                       value="{{ old('metrics.'.$row['key'], $row['values'][$currentYear] ?? '') }}"
                                       class="mnz-input">
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top:18px">
                        <button type="submit" class="mnz-btn mnz-btn--primary">Save manual metrics</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
