{{--
    Social pillar dashboard (/social).

    Shares the .env-* layout classes with the Environmental dashboard on
    purpose: the two pillars should read as the same product, and one set of
    styles cannot drift from itself. Only the accent differs, via
    .env-page--social (Social blue, from the design canvas).

    STANDARD: GRI throughout, with each indicator carrying its own code.
    Status is DERIVED (has a value / does not), never an invented workflow
    state -- no review process exists for disclosure fields.

    The design's "Headcount by function" chart is replaced by GRI 403
    readiness: no function dimension exists in the schema, and the panel's
    real intent was to surface what is still missing.

    Controller data: $company $fiscalYear $social
--}}
@extends('layouts.app')

@section('title', 'Workforce & community - MENetZero')
@section('page-title', 'Social')

@section('content')
@php
    $s = $social;
    $hs = $s['health_safety'];
@endphp

<div class="env-page env-page--social">

    <div class="env-head">
        <div>
            <div class="env-kicker"><span class="env-kicker__dot"></span>Social</div>
            <h1 class="env-title">Workforce &amp; community</h1>
            <p class="env-lead">
                People metrics across the workforce and value chain. Everything here feeds
                GRI 401&ndash;405 and the UAE ESG report.
            </p>
        </div>
        <div class="env-actions">
            @if (Route::has('social.scorecard'))
                <a href="{{ route('social.scorecard', ['fiscal_year' => $s['fiscal_year']]) }}"
                   class="env-btn env-btn--primary">Import HRIS data</a>
            @endif
        </div>
    </div>

    {{-- KPI strip. Each tile states its GRI code, and each comparison uses the
         right unit: headcount moves in PERCENT, rates move in PERCENTAGE
         POINTS. --}}
    <div class="env-kpis">
        @foreach ($s['kpis'] as $kpi)
            <div class="env-kpi">
                <div class="env-kpi__label">{{ $kpi['label'] }}</div>
                <div class="env-kpi__value">
                    @if ($kpi['display'] !== null)
                        {{ $kpi['display'] }}@if ($kpi['unit'])<span class="env-kpi__unit">{{ $kpi['unit'] }}</span>@endif
                    @else
                        <span class="env-kpi__none">&mdash;</span>
                    @endif
                </div>
                <div class="env-kpi__meta">
                    @if ($kpi['display'] === null)
                        <span class="is-warn">not collected</span>
                    @elseif ($kpi['delta'] !== null)
                        @php
                            // A rise is good, bad or neutral depending on the
                            // metric -- turnover up is bad, women in management
                            // up is good, headcount up is neither.
                            $up = $kpi['delta'] > 0;
                            $cls = match ($kpi['direction']) {
                                'lower_better' => $up ? 'is-bad' : 'is-good',
                                'higher_better' => $up ? 'is-good' : 'is-bad',
                                default => '',
                            };
                            if ($kpi['delta'] == 0) { $cls = ''; }
                        @endphp
                        <span class="{{ $cls }}">
                            {{ $up ? '+' : '' }}{{ $kpi['delta'] }}{{ $kpi['delta_kind'] === 'points' ? ' pts' : '%' }}
                            vs FY{{ substr((string) $s['prior_year'], -2) }}
                        </span>
                    @else
                        <span class="is-muted">no prior year</span>
                    @endif
                    <span class="env-kpi__code">{{ $kpi['code'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="env-row">
        {{-- Workforce indicators, this year against last. --}}
        <section class="env-card">
            <div class="env-card__head">
                <h2 class="env-card__title">Workforce indicators</h2>
                <span class="env-card__meta">GRI 401 / 403 / 404 / 405 / 414</span>
            </div>

            <div class="env-tablewrap">
                <table class="env-table">
                    <thead>
                        <tr>
                            <th>Indicator</th>
                            <th class="is-num">FY{{ substr((string) $s['prior_year'], -2) }}</th>
                            <th class="is-num">FY{{ substr((string) $s['fiscal_year'], -2) }}</th>
                            <th class="is-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($s['indicators'] as $row)
                            <tr>
                                <td>
                                    {{ $row['label'] }}
                                    <span class="env-table__code">{{ $row['code'] }}</span>
                                </td>
                                <td class="is-num">{{ $row['prior'] ?? '—' }}</td>
                                <td class="is-num">
                                    @if ($row['current'] !== null)
                                        {{ $row['current'] }}
                                    @else
                                        <span class="is-warn">not collected</span>
                                    @endif
                                </td>
                                <td class="is-status">
                                    @if ($row['complete'])
                                        <span class="env-badge env-badge--green">Complete</span>
                                    @else
                                        <span class="env-badge env-badge--gray">Missing</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- GRI 403 readiness. Replaces the design's headcount-by-function
             chart, which has no data behind it, with the gap it was really
             pointing at. --}}
        <section class="env-card">
            <div class="env-card__head">
                <h2 class="env-card__title">Health &amp; safety readiness</h2>
                <span class="env-card__meta">GRI 403</span>
            </div>

            <div class="soc-hs">
                <div class="soc-hs__score">
                    <span class="soc-hs__pct">{{ $hs['percent'] }}%</span>
                    <span class="soc-hs__lbl">{{ $hs['complete'] }} of {{ $hs['total'] }} fields collected</span>
                </div>
                <div class="soc-hs__bar">
                    <span style="width:{{ $hs['percent'] }}%"></span>
                </div>

                <ul class="soc-hs__list">
                    @foreach ($hs['fields'] as $field)
                        <li class="{{ $field['complete'] ? 'is-done' : '' }}">
                            <span class="soc-hs__tick" aria-hidden="true">{{ $field['complete'] ? '✓' : '' }}</span>
                            {{ $field['label'] }}
                        </li>
                    @endforeach
                </ul>

                {{-- Links straight to the health_safety section editor, not a
                     generic overview: the user is one click from the fields
                     this panel says are missing. --}}
                @if ($hs['complete'] < $hs['total'] && Route::has('gri.sections.edit'))
                    <a href="{{ route('gri.sections.edit', ['section' => 'health_safety', 'fiscal_year' => $s['fiscal_year']]) }}"
                       class="env-btn soc-hs__cta">Complete GRI 403 &rarr;</a>
                @endif
            </div>
        </section>
    </div>

</div>
@endsection
