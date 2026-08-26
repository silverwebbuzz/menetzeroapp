{{--
    MENetZero 2.0 — Reports (Phase 5.6, Reports tab).

    FORM CONTRACT: GET → reports.show with
      fiscal_year (required), location_id (required), moccae_only (checkbox).

    PLAN GATING preserved verbatim — this is the highest-risk part of the page.
    All three exports stay wrapped in x-plan-gated-link with their original
    :allowed / :message / :locked-title / :locked-href expressions:
        PDF   reports.export.pdf    $gate->canExport($pdfExportCode, $reportFy)
        Excel reports.export.excel  $gate->canExport('excel', $reportFy)
        IEQT  reports.export.ieqt   $gate->canExport('ieqt', $reportFy)
    All three are paid capabilities; rendering any as a bare link would hand it
    to every tier (risk R-1, same shape as the SASB export in §25). The
    preview-only / trial-watermark banners and overlays are carried across too.

    SCRIPTS: pushed from the shared partial reports/partials/index-scripts —
    NOT duplicated here (§22 precedent). That partial's DOM contract is honoured
    below: .accordion-header[data-target] / .accordion-body / .accordion-icon,
    #analysisPieChart, and #btnScope / #btnEmission. The toggle buttons keep the
    btn-primary / btn-secondary classes because the shared script swaps those
    exact class names; the mnz- classes ride alongside them.

    Controller data: $locations $fiscalYears $measurement $company $report
    $moccaeOnly $chartPayload $exportReadiness $selectedFiscalYear
    $selectedLocationId $emissionSourceData $resultsBreakdown $error
    Composer data: $gate (PlanGateComposer)

    DELIBERATE OMISSION: none.
--}}
@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@push('styles')
    <style>
        /* Page-local styles. Theme-specific by design — the old theme keeps its
           own block in reports/index.blade.php and the two must not be shared. */
        .rpt-kpi { padding: 16px 18px; }
        .rpt-kpi .kpi-label { font: 500 10.5px var(--mono); letter-spacing: .08em;
            text-transform: uppercase; color: var(--ink-4); }
        .rpt-kpi .kpi-value { font-size: 22px; font-weight: 600; letter-spacing: -.03em;
            color: var(--ink); margin-top: 8px; line-height: 1; }
        .rpt-kpi .kpi-unit { font-size: 11.5px; color: var(--ink-3); margin-top: 6px; }
        .rpt-kpi--highlight { background: var(--accent-tint); }
        .rpt-kpi--highlight .kpi-value { color: var(--accent); }

        .rpt-meta { display: grid; gap: 1px; background: var(--line);
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
        .rpt-meta > div { background: var(--surface); padding: 14px 18px; }
        .rpt-meta .label { font: 500 10.5px var(--mono); letter-spacing: .08em;
            text-transform: uppercase; color: var(--ink-4); }
        .rpt-meta .value { font-size: 13px; font-weight: 600; color: var(--ink); margin-top: 5px; }

        .rpt-note { border: 1px solid var(--warn-line); background: var(--warn-tint);
            border-left-width: 3px; padding: 13px 16px; font-size: 12.5px; color: var(--warn); }

        .rpt-watermark { position: absolute; inset: 0; z-index: 10; display: flex;
            align-items: center; justify-content: center; overflow: hidden;
            pointer-events: none; user-select: none; }
        .rpt-watermark span { font-size: 46px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .16em; transform: rotate(-12deg); color: var(--line-3); }
        .rpt-watermark--trial span { font-size: 36px; color: var(--bad-line); }

        .accordion-icon { transition: transform .35s cubic-bezier(.4,0,.2,1); display: inline-flex; }
        .accordion-body.hidden { display: none; }
        .accordion-header { cursor: pointer; }
        .accordion-header:hover { background: var(--canvas-2); }

        table.mnz-table .t-r { text-align: right; }
        table.mnz-table.rpt-activity th,
        table.mnz-table.rpt-activity td { white-space: nowrap; }
        table.mnz-table.rpt-activity td { vertical-align: top; }
        @media (prefers-reduced-motion: reduce) { .accordion-icon { transition: none; } }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="e">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Reports</div>
            <h1>Reports</h1>
            <p class="mnz-lead">
                Generate your GHG inventory report for a specific location and fiscal year.
                Export to PDF or Excel for internal review or MOCCAE IEQT preparation.
            </p>
        </div>
    </div>

    @if(isset($error))
        <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint)">
            <div class="mnz-panel__body" style="color:var(--bad)">{{ $error }}</div>
        </div>
    @endif

    {{-- Report selector --}}
    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <form method="GET" action="{{ route('reports.show') }}">
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end">
                    <div class="mnz-field">
                        <label class="mnz-label" for="fiscal_year">Fiscal year *</label>
                        <select name="fiscal_year" id="fiscal_year" required class="mnz-select">
                            <option value="">Select year…</option>
                            @if (isset($fiscalYears) && count($fiscalYears) > 0)
                                @foreach ($fiscalYears as $year)
                                    <option value="{{ $year }}"
                                        {{ ($selectedFiscalYear ?? request('fiscal_year')) == $year ? 'selected' : '' }}>
                                        {{ $year }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mnz-field">
                        <label class="mnz-label" for="location_id">Location *</label>
                        <select name="location_id" id="location_id" required class="mnz-select">
                            <option value="">Select location…</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}"
                                    {{ ($selectedLocationId ?? request('location_id')) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="mnz-btn mnz-btn--primary">Generate report</button>
                    </div>
                </div>

                <label style="display:flex;align-items:flex-start;gap:9px;margin-top:14px;cursor:pointer">
                    <input type="checkbox" name="moccae_only" value="1" style="margin-top:2px"
                        {{ ($moccaeOnly ?? request('moccae_only')) ? 'checked' : '' }}>
                    <span>
                        <span style="font-size:12.5px;font-weight:500">MOCCAE format (Scope 1 &amp; 2 only)</span>
                        <span style="display:block;font-size:11.5px;color:var(--ink-3);margin-top:3px">
                            Excludes Scope 3 — use for UAE IEQT submission preparation and official reporting exports.
                        </span>
                    </span>
                </label>
            </form>
        </div>
    </div>

    @if (isset($measurement) && $measurement && isset($report))
        @php
            $moccaeOnly = $moccaeOnly ?? ($report['moccae_only'] ?? false);
            $displayTotal = $report['display_total_tonnes'];
            $totalTonnes = $report['total_tonnes'];
            $scopeTonnes = $report['scope_tonnes'];
            $resultsBreakdown = $report['results_breakdown'];
            $exportParams = array_filter([
                'fiscal_year' => $selectedFiscalYear ?? request('fiscal_year'),
                'location_id' => $selectedLocationId ?? request('location_id'),
                'moccae_only' => $moccaeOnly ? 1 : null,
            ]);
            $reportFyBanner = (int) ($measurement->fiscal_year ?? 0);
            $pdfExportCode = ($moccaeOnly ?? false) ? 'moccae_pdf' : 'ghg_pdf';
            $canDownload = $gate->canExport($pdfExportCode, $reportFyBanner);
            $previewOnly = !$canDownload;
            $trialWatermarked = $canDownload && $gate->exportsAreWatermarked();
        @endphp

        @if($previewOnly)
            <x-preview-only-banner
                :message="$gate->lockedFeatureMessage('In-app preview only. Request a package to download official GHG, Excel, and IEQT exports.', 'Report downloads')"
                :upgrade-label="$gate->upgradeButtonLabel('Request a package')" />
        @elseif($trialWatermarked)
            <x-preview-only-banner
                :message="$gate->watermarkBannerMessage()"
                :upgrade-label="$gate->upgradeButtonLabel('Request a package')" />
        @endif

        <x-export-readiness-banner :readiness="$exportReadiness ?? null" />

        {{-- Report header --}}
        <div class="mnz-panel" style="{{ ($previewOnly || $trialWatermarked) ? 'position:relative' : '' }}">
            @if($previewOnly)
                <div class="rpt-watermark" aria-hidden="true"><span>Preview</span></div>
            @elseif($trialWatermarked)
                <div class="rpt-watermark rpt-watermark--trial" aria-hidden="true"><span>Trial watermark</span></div>
            @endif

            <div class="mnz-panel__head">
                <div style="display:flex;align-items:center;gap:14px;min-width:0">
                    @if($company->logo_url ?? false)
                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }}"
                            style="height:44px;width:auto;object-fit:contain">
                    @endif
                    <div style="min-width:0">
                        <h2 style="font-size:14px;font-weight:600;margin:0">GHG Inventory Summary</h2>
                        <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                            {{ $company->name ?? '' }} — {{ $report['location']->name ?? '' }}
                        </p>
                        <span class="mnz-chip {{ $moccaeOnly ? 'mnz-chip--ok' : '' }}" style="margin-top:7px">
                            {{ $report['export_mode_label'] }}
                        </span>
                        @if($trialWatermarked)
                            <span class="mnz-chip mnz-chip--bad" style="margin-top:7px">Watermarked trial download</span>
                        @endif
                    </div>
                </div>

                @php
                    $reportFy = (int) ($measurement->fiscal_year ?? $selectedFiscalYear ?? 0);
                    $pdfExportCode = ($moccaeOnly ?? false) ? 'moccae_pdf' : 'ghg_pdf';
                    $exportBlocked = !($exportReadiness['is_ready'] ?? true);
                    $moccaeExportBlocked = $exportBlocked && ($moccaeOnly ?? false);
                @endphp

                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <x-plan-gated-link
                        :allowed="$gate->canExport($pdfExportCode, $reportFy) && !$moccaeExportBlocked"
                        :href="route('reports.export.pdf', $exportParams)"
                        :message="$gate->exportMessage($pdfExportCode)"
                        :locked-title="$moccaeExportBlocked ? 'Resolve export readiness issues before downloading MOCCAE PDF.' : null"
                        :locked-href="$moccaeExportBlocked ? '#' : null"
                        class="mnz-btn mnz-btn--primary"
                        locked-class="mnz-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m-3-3l3 3 3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        </svg>
                        Download PDF{{ $trialWatermarked ? ' (trial)' : '' }}
                    </x-plan-gated-link>

                    <x-plan-gated-link
                        :allowed="$gate->canExport('excel', $reportFy)"
                        :href="route('reports.export.excel', $exportParams)"
                        :message="$gate->exportMessage('excel')"
                        class="mnz-btn"
                        locked-class="mnz-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Excel{{ $trialWatermarked ? ' (trial)' : '' }}
                    </x-plan-gated-link>

                    <x-plan-gated-link
                        :allowed="$gate->canExport('ieqt', $reportFy) && !$exportBlocked"
                        :href="route('reports.export.ieqt', $exportParams)"
                        :message="$gate->exportMessage('ieqt')"
                        :locked-title="$exportBlocked ? 'Resolve export readiness issues before IEQT export.' : null"
                        :locked-href="$exportBlocked ? '#' : null"
                        class="mnz-btn"
                        locked-class="mnz-btn"
                        title="CSV pack for MOCCAE IEQT (mrv.ae)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        IEQT Export{{ $trialWatermarked ? ' (trial)' : '' }}
                    </x-plan-gated-link>
                </div>
            </div>

            <div class="rpt-meta">
                <div>
                    <div class="label">Reporting period</div>
                    <div class="value">{{ $report['reporting_period'] }}</div>
                </div>
                <div>
                    <div class="label">Fiscal year</div>
                    <div class="value">{{ $measurement->fiscal_year }}</div>
                </div>
                <div>
                    <div class="label">Status</div>
                    <div class="value">{{ ucfirst($measurement->status ?? 'draft') }}</div>
                </div>
                <div>
                    <div class="label">Activity entries</div>
                    <div class="value">{{ $report['entry_count'] }} records</div>
                </div>
            </div>

            <div class="mnz-seam {{ ($moccaeOnly ?? false) ? 'mnz-seam--3' : 'mnz-seam--4' }}"
                style="border-left:0;border-right:0;border-bottom:0">
                <div class="rpt-kpi">
                    <div class="kpi-label">Scope 1</div>
                    <div class="kpi-value">{{ number_format($scopeTonnes['Scope 1'] ?? 0, 2) }}</div>
                    <div class="kpi-unit">tCO₂e direct</div>
                </div>
                <div class="rpt-kpi">
                    <div class="kpi-label">Scope 2</div>
                    <div class="kpi-value">{{ number_format($scopeTonnes['Scope 2'] ?? 0, 4) }}</div>
                    <div class="kpi-unit">tCO₂e energy</div>
                </div>
                <div class="rpt-kpi rpt-kpi--highlight">
                    <div class="kpi-label">Scope 1 + 2</div>
                    <div class="kpi-value">{{ number_format($report['scope_12_tonnes'], 2) }}</div>
                    <div class="kpi-unit">tCO₂e MOCCAE total</div>
                </div>
                @if(!$moccaeOnly)
                    <div class="rpt-kpi">
                        <div class="kpi-label">Grand total</div>
                        <div class="kpi-value">{{ number_format($totalTonnes, 2) }}</div>
                        <div class="kpi-unit">tCO₂e all scopes</div>
                    </div>
                @endif
            </div>

            <div class="mnz-panel__body" style="display:flex;flex-direction:column;gap:14px">
                @if(empty($company->logo_path))
                    <p style="font-size:12.5px;color:var(--ink-3);margin:0">
                        Tip: <a href="{{ route('client.profile') }}">Upload your company logo</a>
                        in Profile → Company to include it on PDF reports.
                    </p>
                @endif

                <div class="rpt-note">
                    <strong>UAE official submission:</strong> {{ $report['methodology']['disclaimer'] }}
                    Register and submit at <a href="https://mrv.ae" target="_blank" rel="noopener">mrv.ae</a>
                    using the IEQT tool.
                </div>
            </div>
        </div>

        {{-- Emissions breakdown chart --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h2 style="font-size:14px;font-weight:600;margin:0">Emissions Breakdown</h2>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                        Visualise emissions by scope or by source category.
                    </p>
                </div>
                {{-- btn-primary / btn-secondary are required: the shared script in
                     reports/partials/index-scripts swaps those exact class names. --}}
                <div style="display:flex;gap:8px">
                    <button id="btnScope" class="mnz-btn mnz-btn--primary btn-primary">By Scope</button>
                    <button id="btnEmission" class="mnz-btn btn-secondary">By Source</button>
                </div>
            </div>
            <div class="mnz-panel__body">
                <div style="display:flex;justify-content:center">
                    <div style="width:100%;max-width:420px">
                        <canvas id="analysisPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Results breakdown accordion --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h2 style="font-size:14px;font-weight:600;margin:0">Results Breakdown</h2>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                        Click each scope to expand its emission sources. All values in tCO₂e.
                    </p>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="mnz-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="t-r" style="text-align:right">Results (tCO₂e)</th>
                        </tr>
                    </thead>
                    <tbody id="scopeAccordion">
                        @foreach ($resultsBreakdown as $index => $scope)
                            @if($scope['tonnes'] > 0 || $scope['name'] !== 'Scope 3')
                                <tr class="accordion-header" data-target="scope-panel-{{ $index }}"
                                    style="background:var(--canvas)">
                                    <td style="font-weight:600">
                                        <span style="display:flex;align-items:center;gap:8px">
                                            <span class="accordion-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:var(--ink-4)"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </span>
                                            <span>{{ $scope['name'] }}</span>
                                        </span>
                                    </td>
                                    <td class="t-r" style="text-align:right;font-weight:600;color:var(--ink)">
                                        {{ number_format($scope['tonnes'], 2) }}
                                    </td>
                                </tr>

                                <tr id="scope-panel-{{ $index }}" class="accordion-body hidden">
                                    <td colspan="2" style="padding:0;background:var(--surface)">
                                        <table style="width:100%;border-collapse:collapse">
                                            @forelse ($scope['children'] as $child)
                                                <tr>
                                                    <td style="padding:9px 16px 9px 40px;font-size:12.5px;color:var(--ink-2);border-top:1px solid var(--line-2)">
                                                        {{ $child['name'] }}
                                                    </td>
                                                    <td style="padding:9px 16px;font-size:12.5px;color:var(--ink-2);text-align:right;border-top:1px solid var(--line-2)">
                                                        {{ number_format($child['tonnes'], 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" style="padding:9px 16px 9px 40px;font-size:12.5px;color:var(--ink-3);border-top:1px solid var(--line-2)">
                                                        No source-level data
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        @endforeach

                        <tr style="background:var(--accent-tint)">
                            <td style="font-weight:700;color:var(--ink);border-top:2px solid var(--accent)">
                                Total{{ $moccaeOnly ? ' (Scope 1 + 2)' : '' }}
                            </td>
                            <td class="t-r" style="text-align:right;font-weight:700;color:var(--accent);border-top:2px solid var(--accent)">
                                {{ number_format($displayTotal, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Activity register --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h2 style="font-size:14px;font-weight:600;margin:0">Activity Data Register</h2>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                        Line-by-line activity data, emission factors, and calculated emissions.
                    </p>
                </div>
            </div>

            @if($report['activity_register']->isEmpty())
                <div class="mnz-empty">
                    <div class="mnz-empty__title">No activity data</div>
                    <div class="mnz-empty__text">No activity data recorded for this period.</div>
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="mnz-table rpt-activity" style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Scope</th>
                                <th>Source / Activity</th>
                                <th class="t-r" style="text-align:right">Quantity</th>
                                <th>Unit</th>
                                <th class="t-r" style="text-align:right">Factor</th>
                                <th>Methodology</th>
                                <th class="t-r" style="text-align:right">tCO₂e</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['activity_register'] as $row)
                                <tr>
                                    <td>{{ $row['entry_date'] }}</td>
                                    <td>{{ $row['scope'] }}</td>
                                    <td>
                                        <span style="font-weight:500">{{ $row['source'] }}</span>
                                        @if($row['activity'] !== $row['source'])
                                            <br><span style="font-size:11.5px;color:var(--ink-3)">{{ $row['activity'] }}</span>
                                        @endif
                                    </td>
                                    <td class="t-r" style="text-align:right">{{ $row['quantity'] }}</td>
                                    <td>{{ $row['unit'] }}</td>
                                    <td class="t-r" style="text-align:right">
                                        {{ $row['factor_value'] }}
                                        <br><span style="font-size:11px;color:var(--ink-4)">{{ $row['factor_unit'] }}</span>
                                    </td>
                                    <td style="font-size:11.5px">
                                        {{ $row['methodology'] }}
                                        @if($row['reference'] !== '—')
                                            <br><span style="color:var(--ink-4)">{{ $row['reference'] }}</span>
                                        @endif
                                    </td>
                                    <td class="t-r" style="text-align:right;font-weight:500">
                                        {{ number_format($row['tonnes'], 4) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Methodology --}}
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <div>
                    <h2 style="font-size:14px;font-weight:600;margin:0">Methodology</h2>
                    <p style="font-size:12px;color:var(--ink-3);margin:3px 0 0">
                        Calculation standards and emission factor sources used in this inventory.
                    </p>
                </div>
            </div>
            <div class="mnz-panel__body">
                <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin:0">
                    <div>
                        <dt style="font-size:12.5px;font-weight:600">Framework</dt>
                        <dd style="font-size:12.5px;color:var(--ink-2);margin:5px 0 0">{{ $report['methodology']['framework'] }}</dd>
                    </div>
                    <div>
                        <dt style="font-size:12.5px;font-weight:600">Emission factors</dt>
                        <dd style="font-size:12.5px;color:var(--ink-2);margin:5px 0 0">{{ $report['methodology']['factors'] }}</dd>
                    </div>
                    <div>
                        <dt style="font-size:12.5px;font-weight:600">Scopes included</dt>
                        <dd style="font-size:12.5px;color:var(--ink-2);margin:5px 0 0">{{ $report['methodology']['scopes'] }}</dd>
                    </div>
                    <div>
                        <dt style="font-size:12.5px;font-weight:600">GWP values</dt>
                        <dd style="font-size:12.5px;color:var(--ink-2);margin:5px 0 0">{{ $report['methodology']['gwp'] }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    {{-- Shared with the old theme — see reports/partials/index-scripts.blade.php. --}}
    @include('reports.partials.index-scripts')
@endpush
