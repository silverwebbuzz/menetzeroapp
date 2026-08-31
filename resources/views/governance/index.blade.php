{{--
    Governance pillar dashboard (/governance).

    Shares the .env-* layout with the Environmental and Social dashboards so
    all three pillars read as one product; only the accent differs, via
    .env-page--gov (Governance purple, from the design canvas).

    STANDARD: IFRS S1/S2 governance plus GRI 2, 205 and 418. Every row states
    its code.

    NOT SHOWN, ON PURPOSE:
      - Board independence. The app stores board_diversity_percent (WOMEN on
        the board); independence is NON-EXECUTIVE directors. Different
        measures -- showing one under the other's label would be a
        misstatement in a regulated disclosure.
      - Policies register. Needs an entity with owner, review date and
        approval status per policy. The governance DISCLOSURE completeness
        below answers the same question ("what is missing") with real data.

    Controller data: $company $fiscalYear $gov
--}}
@extends('layouts.app')

@section('title', 'Board, ethics & risk - MENetZero')
@section('page-title', 'Governance')

@section('content')
@php
    $d = $gov['disclosures'];
    $rem = $gov['remuneration'];
@endphp

<div class="env-page env-page--gov">

    <div class="env-head">
        <div>
            <div class="env-kicker"><span class="env-kicker__dot"></span>Governance</div>
            <h1 class="env-title">Board, ethics &amp; risk</h1>
            <p class="env-lead">
                Oversight structures, conduct disclosures and the sustainability risk
                register. Everything here feeds IFRS S1, IFRS S2 and GRI 2.
            </p>
        </div>
        <div class="env-actions">
            @if (Route::has('gov.risks'))
                <a href="{{ route('gov.risks', ['fiscal_year' => $gov['fiscal_year']]) }}"
                   class="env-btn env-btn--primary">Risk register</a>
            @endif
        </div>
    </div>

    {{-- KPI strip. A count of 0 is a REAL answer for incidents and breaches --
         it is reported, not missing -- so it never renders as "not collected". --}}
    <div class="env-kpis">
        @foreach ($gov['kpis'] as $kpi)
            <div class="env-kpi">
                <div class="env-kpi__label">{{ $kpi['label'] }}</div>
                <div class="env-kpi__value">
                    @if ($kpi['display'] !== null)
                        <span class="{{ strlen((string) $kpi['display']) > 6 ? 'env-kpi__value--text' : '' }}">{{ $kpi['display'] }}</span>
                    @else
                        <span class="env-kpi__none">&mdash;</span>
                    @endif
                </div>
                <div class="env-kpi__meta">
                    @if ($kpi['display'] === null)
                        <span class="is-warn">not collected</span>
                    @elseif ($kpi['meta'])
                        <span class="{{ $kpi['meta_missing'] ? 'is-warn' : '' }}">{{ $kpi['meta'] }}</span>
                    @endif
                    <span class="env-kpi__code">{{ $kpi['code'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="env-row">
        {{-- Governance disclosures. Replaces the design's policies register:
             same question ("what is still missing"), real data. --}}
        <section class="env-card">
            <div class="env-card__head">
                <h2 class="env-card__title">Governance disclosures</h2>
                <span class="env-card__meta">{{ $d['complete'] }} of {{ $d['total'] }} complete</span>
            </div>

            <div class="gov-progress">
                <div class="gov-progress__bar"><span style="width:{{ $d['percent'] }}%"></span></div>
            </div>

            <div class="env-tablewrap">
                <table class="env-table">
                    <thead>
                        <tr>
                            <th>Disclosure</th>
                            <th>Framework</th>
                            <th class="is-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($d['rows'] as $row)
                            <tr>
                                <td>
                                    {{ $row['label'] }}
                                    <span class="env-table__code">{{ $row['code'] }}</span>
                                    @if ($row['complete'] && strlen($row['value']) <= 60)
                                        <div class="gov-answer">{{ $row['value'] }}</div>
                                    @endif
                                </td>
                                <td class="gov-fw">{{ $row['framework'] }}</td>
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

        <div class="gov-side">
            {{-- Ethics & conduct KPIs. --}}
            <section class="env-card">
                <div class="env-card__head">
                    <h2 class="env-card__title">Ethics &amp; conduct</h2>
                    <span class="env-card__meta">GRI 205 / 418</span>
                </div>
                <dl class="gov-metrics">
                    @foreach ($gov['conduct'] as $metric)
                        <div class="gov-metric">
                            <dt>
                                {{ $metric['label'] }}
                                <span class="env-table__code">{{ $metric['code'] }}</span>
                            </dt>
                            <dd class="{{ $metric['display'] === null ? 'is-missing' : '' }}">
                                {{ $metric['display'] ?? 'not collected' }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- Remuneration linkage. Reports only whether the question has
                 been ANSWERED -- the field is free text, and parsing it for a
                 yes/no could misstate whether pay is linked to ESG. --}}
            <section class="env-card {{ $rem['answered'] ? '' : 'env-card--dashed' }}">
                <div class="env-card__head">
                    <h2 class="env-card__title">Remuneration linkage</h2>
                    <span class="env-card__meta">GRI 2-19</span>
                </div>
                <div class="gov-rem">
                    @if ($rem['answered'])
                        @if ($rem['climate'])
                            <div class="gov-rem__item">
                                <span class="gov-rem__lbl">Climate metrics</span>
                                <p>{{ \Illuminate\Support\Str::limit($rem['climate'], 180) }}</p>
                            </div>
                        @endif
                        @if ($rem['sustainability'])
                            <div class="gov-rem__item">
                                <span class="gov-rem__lbl">Sustainability metrics</span>
                                <p>{{ \Illuminate\Support\Str::limit($rem['sustainability'], 180) }}</p>
                            </div>
                        @endif
                    @else
                        <p class="gov-rem__empty">
                            IFRS S1 and GRI 2-19 ask whether ESG performance affects executive pay.
                            Not answered for FY{{ $gov['fiscal_year'] }}.
                        </p>
                        @if (Route::has('disclosures.s1.sections.edit'))
                            <a href="{{ route('disclosures.s1.sections.edit', ['section' => 'governance', 'fiscal_year' => $gov['fiscal_year']]) }}"
                               class="env-btn">Answer in IFRS S1 &rarr;</a>
                        @endif
                    @endif
                </div>
            </section>
        </div>
    </div>

</div>
@endsection
