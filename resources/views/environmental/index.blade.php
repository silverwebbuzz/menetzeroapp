{{--
    Environmental pillar dashboard (/environmental).

    SHARED BY BOTH THEMES. Plain markup with self-contained classes, like the
    Overview ESG cards -- neither shell's stylesheet needs to know about it.

    The E+S+G scorecards on /dashboard are a DIFFERENT page and are untouched.

    STANDARD: GHG Protocol Corporate Standard. Scope 2 is location-based (the
    default presentation); Scope 3 completeness is measured against the
    standard's fifteen categories.

    STATUS BADGES USE THE REAL MEASUREMENT ENUM (draft / submitted /
    under_review / not_verified / verified). There is deliberately no
    "estimated" badge: no estimated-vs-measured flag exists in the schema, and
    inventing one would misrepresent data quality.

    Controller data: $company $fiscalYear $env
--}}
@extends('layouts.app')

@section('title', 'Emissions & climate - MENetZero')
@section('page-title', 'Environmental')

@section('content')
@php
    $k = $env['kpis'];
    $fmt = fn ($v, $d = 1) => number_format((float) $v, $d);
@endphp

<div class="env-page">

    <div class="env-head">
        <div>
            <div class="env-kicker"><span class="env-kicker__dot"></span>Environmental</div>
            <h1 class="env-title">Emissions &amp; climate</h1>
            <p class="env-lead">
                GHG inventory, energy, and the decarbonisation pathway. Everything here
                feeds IFRS S2 and the GHG Protocol inventory.
            </p>
        </div>
        <div class="env-actions">
            @if (Route::has('env.measure'))
                <a href="{{ route('env.measure') }}" class="env-btn env-btn--primary">Add measurement</a>
            @endif
        </div>
    </div>

    {{-- KPI strip. Gross plus each scope; shares are of gross, so they sum to
         100 and a reader can see which scope dominates. --}}
    <div class="env-kpis">
        <div class="env-kpi">
            <div class="env-kpi__label">Gross emissions</div>
            <div class="env-kpi__value">{{ $fmt($k['total']) }}<span class="env-kpi__unit">tCO<sub>2</sub>e</span></div>
            <div class="env-kpi__meta">
                @if ($k['baseline_change'])
                    <span class="{{ $k['baseline_change']['percent'] <= 0 ? 'is-good' : 'is-bad' }}">
                        {{ $k['baseline_change']['percent'] > 0 ? '+' : '' }}{{ $fmt($k['baseline_change']['percent']) }}%
                        vs FY{{ substr((string) $k['baseline_change']['base_year'], -2) }}
                    </span>
                @else
                    <span class="is-muted">no baseline set</span>
                @endif
            </div>
        </div>

        <div class="env-kpi">
            <div class="env-kpi__label">Scope 1</div>
            <div class="env-kpi__value">{{ $fmt($k['scope1']['tonnes']) }}<span class="env-kpi__unit">tCO<sub>2</sub>e</span></div>
            <div class="env-kpi__meta">
                {{ $k['scope1']['share'] !== null ? $k['scope1']['share'] . '% of total' : '—' }}
            </div>
        </div>

        <div class="env-kpi">
            <div class="env-kpi__label">Scope 2 (location)</div>
            <div class="env-kpi__value">{{ $fmt($k['scope2']['tonnes']) }}<span class="env-kpi__unit">tCO<sub>2</sub>e</span></div>
            <div class="env-kpi__meta">
                {{ $k['scope2']['share'] !== null ? $k['scope2']['share'] . '% of total' : '—' }}
            </div>
        </div>

        <div class="env-kpi">
            <div class="env-kpi__label">Scope 3</div>
            <div class="env-kpi__value">{{ $fmt($k['scope3']['tonnes']) }}<span class="env-kpi__unit">tCO<sub>2</sub>e</span></div>
            <div class="env-kpi__meta">
                @php $cov = $k['scope3']['coverage']; @endphp
                <span class="{{ $cov['reported'] < $cov['total'] ? 'is-warn' : '' }}">
                    {{ $cov['reported'] }} of {{ $cov['total'] }} categories
                </span>
            </div>
        </div>
    </div>

    <div class="env-row">
        {{-- Emissions by source, this year vs last, largest first. --}}
        <section class="env-card">
            <div class="env-card__head">
                <h2 class="env-card__title">Emissions by source</h2>
                <span class="env-card__meta">FY{{ $env['fiscal_year'] }} · tCO<sub>2</sub>e</span>
            </div>

            @if (empty($env['sources']))
                <div class="env-empty">
                    No measurements recorded for FY{{ $env['fiscal_year'] }} yet.
                    @if (Route::has('env.measure'))
                        <a href="{{ route('env.measure') }}">Add your first measurement &rarr;</a>
                    @endif
                </div>
            @else
                <div class="env-tablewrap">
                    <table class="env-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th class="is-num">FY{{ substr((string) $env['prior_year'], -2) }}</th>
                                <th class="is-num">FY{{ substr((string) $env['fiscal_year'], -2) }}</th>
                                <th class="is-status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($env['sources'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="is-num">{{ $row['prior'] !== null ? $fmt($row['prior']) : '—' }}</td>
                                    <td class="is-num">
                                        {{ $row['current'] !== null ? $fmt($row['current']) : 'not collected' }}
                                    </td>
                                    <td class="is-status">
                                        @if ($row['status_label'])
                                            <span class="env-badge env-badge--{{ $row['status_color'] }}">{{ $row['status_label'] }}</span>
                                        @else
                                            <span class="env-badge env-badge--gray">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Scope mix. Inline SVG donut -- three arcs need no chart library,
             and this keeps the page theme-agnostic. --}}
        <section class="env-card">
            <div class="env-card__head">
                <h2 class="env-card__title">Scope mix</h2>
            </div>

            @php
                $mix = $env['scope_mix'];
                $mixTotal = array_sum(array_column($mix, 'tonnes'));
                $inks = ['Scope 1' => '#1b6b45', 'Scope 2' => '#3f9068', 'Scope 3' => '#8fc3a8'];
                // Circumference of r=54: arcs are drawn with dash offsets so no
                // path maths is needed.
                $circ = 2 * M_PI * 54;
                $offset = 0;
            @endphp

            @if ($mixTotal <= 0)
                <div class="env-empty">No emissions data for FY{{ $env['fiscal_year'] }} yet.</div>
            @else
                <div class="env-donut">
                    <svg viewBox="0 0 140 140" width="180" height="180" role="img"
                         aria-label="Scope mix for FY{{ $env['fiscal_year'] }}">
                        <g transform="rotate(-90 70 70)">
                            @foreach ($mix as $seg)
                                @if ($seg['tonnes'] > 0)
                                    @php
                                        $len = ($seg['tonnes'] / $mixTotal) * $circ;
                                        $dash = round($len, 2) . ' ' . round($circ - $len, 2);
                                        $thisOffset = round(-$offset, 2);
                                        $offset += $len;
                                    @endphp
                                    <circle cx="70" cy="70" r="54" fill="none"
                                            stroke="{{ $inks[$seg['label']] }}" stroke-width="22"
                                            stroke-dasharray="{{ $dash }}"
                                            stroke-dashoffset="{{ $thisOffset }}"/>
                                @endif
                            @endforeach
                        </g>
                    </svg>

                    <ul class="env-legend">
                        @foreach ($mix as $seg)
                            <li>
                                <i style="background:{{ $inks[$seg['label']] }}"></i>
                                <span class="env-legend__name">{{ $seg['label'] }}</span>
                                <span class="env-legend__val">{{ $fmt($seg['percent']) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>

    {{-- Emissions trend, stacked by scope. Reuses the SAME yearlyTrend() the
         main dashboard chart uses, so a year's total here can never disagree
         with the one shown there. Stacked rather than a single line because
         the composition shift is the story -- a falling total with rising
         Scope 3 is a different situation from a falling total overall. --}}
    @if (!empty($env['trend']['labels']))
        <section class="env-card env-card--trend">
            <div class="env-card__head">
                <h2 class="env-card__title">Emissions trend</h2>
                <span class="env-card__meta">tCO<sub>2</sub>e by scope</span>
            </div>

            @if (!$env['trend']['has_multiple'])
                <div class="env-empty">
                    Only FY{{ $env['trend']['labels'][0] }} has data. A trend appears once a
                    second reporting year is recorded.
                </div>
            @else
                @php
                    $t = $env['trend'];
                    $tMax = $t['max'];
                    $inks = ['scope1' => '#1b6b45', 'scope2' => '#3f9068', 'scope3' => '#8fc3a8'];
                @endphp
                <div class="env-trend">
                    @foreach ($t['labels'] as $i => $label)
                        @php
                            $yearTotal = (float) ($t['values'][$i] ?? 0);
                            $colHeight = $tMax > 0 ? ($yearTotal / $tMax) * 100 : 0;
                        @endphp
                        <div class="env-trend__col">
                            <div class="env-trend__barwrap">
                                <div class="env-trend__bar" style="height:{{ round($colHeight, 2) }}%"
                                     title="FY{{ $label }} — {{ number_format($yearTotal, 1) }} tCO2e">
                                    @foreach (['scope3', 'scope2', 'scope1'] as $scopeKey)
                                        @php
                                            $part = (float) ($t[$scopeKey][$i] ?? 0);
                                            $share = $yearTotal > 0 ? ($part / $yearTotal) * 100 : 0;
                                        @endphp
                                        @if ($share > 0)
                                            <span style="height:{{ round($share, 2) }}%;background:{{ $inks[$scopeKey] }}"></span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="env-trend__val">{{ number_format($yearTotal, 0) }}</div>
                            <div class="env-trend__year">FY{{ substr((string) $label, -2) }}</div>
                        </div>
                    @endforeach
                </div>
                <ul class="env-legend env-legend--inline">
                    <li><i style="background:{{ $inks['scope1'] }}"></i><span class="env-legend__name">Scope 1</span></li>
                    <li><i style="background:{{ $inks['scope2'] }}"></i><span class="env-legend__name">Scope 2</span></li>
                    <li><i style="background:{{ $inks['scope3'] }}"></i><span class="env-legend__name">Scope 3</span></li>
                </ul>
            @endif
        </section>
    @endif

</div>
@endsection
