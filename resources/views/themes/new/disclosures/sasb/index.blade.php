{{--
    MENetZero 2.0 — SASB disclosure index (Phase 5.5, Governance).

    FORM CONTRACT: sasb_sector → disclosures.sasb.sector, carrying
    ['fiscal_year' => $fiscalYear].

    PLAN GATING preserved: the CSV export is wrapped in x-plan-gated-link with
    $gate->canDisclosureExportType('sasb_index', $fiscalYear). Rendering a bare
    link here would hand a paid export to every tier (risk R-1).

    Controller data: $index $sectors $selectedSector $fiscalYear
--}}
@extends('layouts.app')

@section('title', 'SASB Index')
@section('page-title', 'SASB Disclosure Index')

@section('content')
<div class="mnz-stack" data-pillar="g">

    {{-- Framework tab strip removed: this page is a register owned by

         its pillar, not a section of a framework. The lineage line names

         the reports that read it instead. --}}

    @include('layouts.partials.register-lineage')

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · SASB</div>
            <h1>Disclosure index</h1>
            <p class="mnz-lead">
                Metrics auto-link from your GHG inventory and GRI disclosures where mapped.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok)">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <div>
                <h3>SASB sector</h3>
                <p class="mnz-muted">Select the SASB industry standard for {{ $fiscalYear }}.</p>
            </div>
        </div>
        <div class="mnz-panel__body">
            <form method="POST" action="{{ route('disclosures.sasb.sector', ['fiscal_year' => $fiscalYear]) }}"
                  style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
                @csrf
                <div class="mnz-field" style="flex:1;min-width:min(100%,280px)">
                    <label class="mnz-label" for="sasb_sector">Industry sector</label>
                    <select class="mnz-select" id="sasb_sector" name="sasb_sector">
                        <option value="">— Not applicable / not selected —</option>
                        @foreach ($sectors as $code => $sector)
                            <option value="{{ $code }}" @selected($selectedSector === $code)>{{ $code }} — {{ $sector['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="mnz-btn mnz-btn--primary">Save sector</button>
            </form>
        </div>
    </div>

    @if ($index['sector'])
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h3>{{ $index['sector_label'] }}</h3>
                    <p class="mnz-muted">{{ $index['sector'] }} · {{ $index['industry'] }}</p>
                </div>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('sasb_index', $fiscalYear)"
                    :href="route('disclosures.sasb.export', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="mnz-btn mnz-btn--ghost"
                    locked-class="mnz-btn mnz-btn--ghost">
                    Export CSV
                </x-plan-gated-link>
            </div>

            <div class="mnz-table" style="--cols: 140px 2fr 1fr 1fr 1fr 1fr">
                <div class="mnz-table__head">
                    <span>SASB code</span><span>Metric</span><span>Unit</span>
                    <span>Value</span><span>Status</span><span>Source</span>
                </div>
                @foreach ($index['metrics'] as $row)
                    <div class="mnz-table__row">
                        <span class="mnz-mono">{{ $row['code'] }}</span>
                        <span class="t-name">{{ $row['label'] }}</span>
                        <span class="mnz-muted">{{ $row['unit'] }}</span>
                        <span class="mnz-mono">{{ $row['value'] ?? '—' }}</span>
                        <span>{{ $row['status'] }}</span>
                        <span class="mnz-muted" style="text-transform:uppercase;font-size:11px">{{ $row['source'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mnz-panel__foot">
                <span class="mnz-muted">
                    Manual SASB metrics (e.g. air emissions) can be entered via ESG Scorecard
                    import using metric keys defined in <span class="mnz-mono">config/sasb.php</span>.
                </span>
            </div>
        </div>
    @else
        <div class="mnz-panel">
            <div class="mnz-panel__body">
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No sector selected</div>
                    <div class="mnz-empty__text">
                        Select a SASB sector above to generate the disclosure index for this reporting year.
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
