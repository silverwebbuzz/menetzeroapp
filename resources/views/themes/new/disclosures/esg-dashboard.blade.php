{{--
    MENetZero 2.0 - ESG dashboard (Phase 6 body migration).

    This is the landing page for the E / S / G tabs, so it is the most-seen
    remaining old body. No plan gating and no scripts - verified against the
    original, zero gate calls and zero script blocks.

    SHARED PARTIAL: disclosures.partials.year-select is included UNCHANGED.
    It is self-contained (its own form, its own inline PHP block, options from
    $availableYears via ReportingYearsComposer) and styles itself with inline
    Tailwind, which the new shell still loads. It therefore keeps the old look
    but works correctly. Forking it per theme would duplicate the year-fallback
    logic, which several disclosure pages depend on.

    Routes preserved: disclosures.esg-dashboard (the year form target),
    disclosures.s2.targets.index (x2), disclosures.esg-scorecard.index.

    Controller data: $company $fiscalYear $dashboard
    Composer data: $availableYears (ReportingYearsComposer, read by the partial)
--}}
@extends('layouts.app')

@section('title', 'ESG Dashboard - MenetZero')
@section('page-title', 'ESG Dashboard')

@push('styles')
    <style>
        .esg-score { text-align: center; padding: 24px 20px; }
        .esg-score__val { font-size: 40px; font-weight: 600; letter-spacing: -.04em;
            color: var(--accent); line-height: 1; margin-top: 8px; }
        .esg-check { display: flex; align-items: center; gap: 9px; font-size: 12.5px; padding: 4px 0; }
        .esg-check__on { color: var(--ok); }
        .esg-check__off { color: var(--ink-4); }
        .esg-facts { display: grid; gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); }
        .esg-facts .k { font: 500 10.5px var(--mono); letter-spacing: .08em;
            text-transform: uppercase; color: var(--ink-4); }
        .esg-facts .v { font-size: 13px; font-weight: 600; margin-top: 4px; }
        .esg-bar { height: 6px; background: var(--line-2); overflow: hidden; margin-top: 4px; }
        .esg-bar span { display: block; height: 100%; background: var(--accent); }
        .esg-bar span.is-good { background: var(--ok); }
        .esg-bar span.is-bad { background: var(--bad); }
        .esg-target + .esg-target { border-top: 1px solid var(--line-2); }
        .esg-target { padding: 16px 0; }
        table.mnz-table td.t-r, table.mnz-table th.t-r { text-align: right; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="neutral">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">E + S + G scorecards</div>
            <h1>{{ $company->name }}</h1>
        </div>
        <div class="mnz-pagehead__actions">
            @include('disclosures.partials.year-select', [
                'action' => route('disclosures.esg-dashboard'),
                'label' => 'Year',
            ])
        </div>
    </div>

    {{-- Overall --}}
    <div class="mnz-panel">
        <div class="esg-score">
            <div class="mnz-label">Overall ESG readiness</div>
            <div class="esg-score__val">{{ $dashboard['overall'] }}%</div>
        </div>
    </div>

    {{-- Pillars --}}
    <div class="mnz-seam mnz-seam--3">
        @foreach(['environmental', 'social', 'governance'] as $pillar)
            @php $p = $dashboard[$pillar]; @endphp
            <div data-pillar="{{ $pillar === 'environmental' ? 'e' : ($pillar === 'social' ? 's' : 'g') }}">
                <div class="mnz-panel__head" style="border-bottom:1px solid var(--line)">
                    <h3 style="font-size:14px;font-weight:600;margin:0">{{ $p['label'] }}</h3>
                    <span style="font-size:17px;font-weight:600;color:var(--accent)">{{ $p['percent'] }}%</span>
                </div>
                <div class="mnz-panel__body">
                    @foreach($p['checks'] as $check)
                        <div class="esg-check">
                            <span class="{{ $check['done'] ? 'esg-check__on' : 'esg-check__off' }}">{{ $check['done'] ? '✓' : '○' }}</span>
                            <span>{{ $check['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- GHG summary --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">GHG summary (GRI 305 / IFRS S2)</h3>
        </div>
        <div class="mnz-panel__body">
            @if($dashboard['ghg_summary']['has_data'])
                <div class="esg-facts">
                    <div><div class="k">Scope 1</div><div class="v">{{ number_format($dashboard['ghg_summary']['scope1'], 1) }} tCO₂e</div></div>
                    <div><div class="k">Scope 2</div><div class="v">{{ number_format($dashboard['ghg_summary']['scope2'], 1) }} tCO₂e</div></div>
                    <div><div class="k">Scope 3</div><div class="v">{{ number_format($dashboard['ghg_summary']['scope3'], 1) }} tCO₂e</div></div>
                    <div><div class="k">Total</div><div class="v">{{ number_format($dashboard['ghg_summary']['total_tonnes'], 1) }} tCO₂e</div></div>
                </div>
            @else
                <p style="font-size:12.5px;color:var(--ink-3);margin:0">No GHG data for {{ $fiscalYear }}.</p>
            @endif
        </div>
    </div>

    {{-- Climate targets vs actuals. Targets are captured under Disclosures →
         Targets; this is where they meet the inventory. --}}
    <div class="mnz-panel">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Climate targets vs actual</h3>
            <a href="{{ route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]) }}" style="font-size:12.5px">Manage targets</a>
        </div>
        <div class="mnz-panel__body">
            @forelse($dashboard['targets'] as $t)
                @php
                    $chip = match($t['status']['key']) {
                        'achieved', 'on_track' => 'mnz-chip--ok',
                        'off_track', 'missed' => 'mnz-chip--bad',
                        'no_data', 'incomplete' => '',
                        default => 'mnz-chip--warn',
                    };
                    $barClass = match($t['status']['key']) {
                        'achieved', 'on_track' => 'is-good',
                        'off_track', 'missed' => 'is-bad',
                        default => '',
                    };
                @endphp
                <div class="esg-target">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px">
                        <div>
                            <div style="font-weight:600;font-size:13px">{{ $t['name'] }}</div>
                            <div style="font-size:11.5px;color:var(--ink-3);margin-top:2px">
                                Target {{ $t['target_year'] }} · {{ $t['scope_label'] }}
                                @if($t['sbti_aligned']) · <span style="color:var(--accent)">SBTi aligned</span> @endif
                            </div>
                        </div>
                        <span class="mnz-chip {{ $chip }}">{{ $t['status']['label'] }}</span>
                    </div>

                    <div class="esg-facts" style="margin-bottom:12px">
                        <div>
                            <div class="k">Baseline{{ $t['base_year'] ? ' ' . $t['base_year'] : '' }}</div>
                            <div class="v">{{ $t['baseline_tco2e'] !== null ? number_format($t['baseline_tco2e'], 2) . ' tCO₂e' : '—' }}</div>
                        </div>
                        <div>
                            <div class="k">Current {{ $t['current_year'] }}</div>
                            <div class="v">{{ $t['current_tco2e'] !== null ? number_format($t['current_tco2e'], 2) . ' tCO₂e' : '—' }}</div>
                            @if($t['change_vs_baseline_percent'] !== null)
                                <div style="font-size:11px;margin-top:3px;color:{{ $t['change_vs_baseline_percent'] <= 0 ? 'var(--ok)' : 'var(--bad)' }}">
                                    {{ $t['change_vs_baseline_percent'] <= 0 ? '▼' : '▲' }}
                                    {{ number_format(abs($t['change_vs_baseline_percent']), 1) }}% vs baseline
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="k">Target {{ $t['target_year'] }}</div>
                            <div class="v" style="color:var(--accent)">{{ $t['target_tco2e'] !== null ? number_format($t['target_tco2e'], 2) . ' tCO₂e' : '—' }}</div>
                            @if($t['target_is_derived'])
                                <div style="font-size:11px;color:var(--ink-4);margin-top:3px">derived from {{ number_format($t['reduction_percent'], 1) }}%</div>
                            @endif
                        </div>
                        <div>
                            <div class="k">Still to reduce</div>
                            <div class="v">{{ $t['remaining_tco2e'] !== null ? number_format($t['remaining_tco2e'], 2) . ' tCO₂e' : '—' }}</div>
                        </div>
                    </div>

                    @if($t['achieved_percent'] !== null)
                        <div class="esg-bar"><span class="{{ $barClass }}" style="width: {{ $t['achieved_percent'] }}%"></span></div>
                        <div style="font-size:11px;color:var(--ink-3);margin-top:5px">{{ $t['achieved_percent'] }}% of required reduction achieved</div>
                    @elseif($t['status']['key'] === 'no_data')
                        <p style="font-size:11.5px;color:var(--ink-3);margin:0">No GHG inventory data for {{ $fiscalYear }} — enter emissions to track progress.</p>
                    @else
                        <p style="font-size:11.5px;color:var(--ink-3);margin:0">Add a baseline and target value to track progress.</p>
                    @endif
                </div>
            @empty
                <p style="font-size:12.5px;color:var(--ink-3);margin:0">
                    No reduction targets set.
                    <a href="{{ route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear]) }}">Add a target</a>
                    to compare your inventory against where you plan to be.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Framework readiness --}}
    <div class="mnz-seam mnz-seam--3">
        @foreach(['ifrs_s2' => 'IFRS S2', 'ifrs_s1' => 'IFRS S1', 'gri' => 'GRI'] as $key => $label)
            <div class="mnz-kpi">
                <div class="mnz-label">{{ $label }}</div>
                <div class="mnz-kpi__value"><b style="color:var(--accent)">{{ $dashboard['frameworks'][$key]['percent'] }}%</b></div>
            </div>
        @endforeach
    </div>

    {{-- Scorecard preview --}}
    <div class="mnz-pagehead" style="border-bottom:0;padding-bottom:0">
        <div>
            <h2 style="font-size:17px;font-weight:600;margin:0">ESG Scorecard preview</h2>
            <p class="mnz-lead" style="margin-top:6px">3-year KPI tables — {{ implode(', ', $dashboard['scorecard']['years']) }}</p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear]) }}" class="mnz-btn">Open full scorecard</a>
        </div>
    </div>

    @foreach($dashboard['scorecard']['categories'] as $catKey => $category)
        <div class="mnz-panel">
            <div class="mnz-panel__head">
                <h3 style="font-size:14px;font-weight:600;margin:0">{{ $category['title'] }}</h3>
            </div>
            <div style="overflow-x:auto">
                <table class="mnz-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            @foreach($dashboard['scorecard']['years'] as $year)
                                <th class="t-r">{{ $year }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['rows'] as $row)
                            <tr>
                                <td>
                                    <div>{{ $row['label'] }}</div>
                                    @if(!empty($row['unit']))
                                        <div style="font-size:11px;color:var(--ink-4);margin-top:2px">{{ $row['unit'] }}</div>
                                    @endif
                                </td>
                                @foreach($dashboard['scorecard']['years'] as $year)
                                    <td class="t-r">
                                        @php $val = $row['values'][$year] ?? null; @endphp
                                        {{ $val !== null ? number_format($val, $row['decimals']) : '—' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
