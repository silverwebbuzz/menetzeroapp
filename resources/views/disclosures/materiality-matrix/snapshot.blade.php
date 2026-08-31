{{--
    Materiality snapshot (Overview) - READ ONLY.

    Two pages, one source. The SCORING FORM lives at
    disclosures/materiality-matrix/index under Governance; this is the same
    data with nothing editable, so the snapshot can never show a value the
    form has not saved.

    Layout follows Menetzero-Redesign/Internal.dc.html: plot left, topics
    table right, 50/50, collapsing below 900px. Shares the .mm-* classes with
    the form page so a topic cannot be plotted differently in the two.

    Controller data: $company $fiscalYear $topics
--}}
@extends('layouts.app')

@section('title', 'Materiality matrix - MENetZero')
@section('page-title', 'Materiality')

@section('content')
@php
    $levels = ['low' => 0, 'medium' => 1, 'high' => 2];
    $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];

    $plotted = [];
    foreach ($topics as $key => $t) {
        $ix = $levels[$t['impact_materiality'] ?: 'low'] ?? 0;
        $fx = $levels[$t['financial_materiality'] ?: 'low'] ?? 0;

        // Deterministic nudge from the topic key: identical on every render,
        // and topics sharing a cell separate rather than stack.
        $seed = crc32($key);
        $jx = (($seed % 40) - 20) / 100;
        $jy = ((($seed >> 8) % 40) - 20) / 100;

        $plotted[] = [
            'label' => $t['label'],
            'ink' => $pillarInk[$t['pillar'] ?? ''] ?? '#8b9199',
            'left' => round((($fx + 0.5 + $jx) / 3) * 100, 2),
            'bottom' => round((($ix + 0.5 + $jy) / 3) * 100, 2),
        ];
    }

    $materialCount = collect($topics)->where('is_material', true)->count();
@endphp

<div class="w-full">
    <div class="mm-head">
        <div>
            <div class="mm-kicker">Overview · Snapshot</div>
            <h1 class="mm-title">Materiality matrix</h1>
            <p class="mm-lead">
                Double materiality: impact on the world against financial effect on the business.
                <strong>{{ $materialCount }}</strong> of {{ count($topics) }} topics flagged material for FY{{ $fiscalYear }}.
            </p>
        </div>
        <div class="mm-head__actions">
            {{-- The only action here: this page reports, the form edits. --}}
            <a href="{{ route('disclosures.materiality-matrix.index', ['fiscal_year' => $fiscalYear]) }}"
               class="btn btn-primary btn-sm">Edit scores</a>
        </div>
    </div>

    <div class="mm-grid">
        <div class="mm-card">
            <div class="mm-card__head">
                <span class="mm-axis">Impact &uarr; / Financial &rarr;</span>
                <span class="mm-year">FY{{ $fiscalYear }}</span>
            </div>
            <div class="mm-plot">
                <div class="mm-plot__grid"></div>
                @foreach ($plotted as $pt)
                    <div class="mm-pt" style="left:{{ $pt['left'] }}%;bottom:{{ $pt['bottom'] }}%">
                        <span class="mm-pt__dot" style="background:{{ $pt['ink'] }}"></span>
                        <span class="mm-pt__name">{{ $pt['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mm-keys">
                <span><i style="background:#0f7a4a"></i>Environmental</span>
                <span><i style="background:#1a6c9e"></i>Social</span>
                <span><i style="background:#5b5aa8"></i>Governance</span>
            </div>
        </div>

        <div class="mm-card mm-card--flush">
            <div class="mm-table__title">Material topics</div>
            <div class="mm-table__head">
                <span>Topic</span>
                <span class="mm-r">GRI</span>
                <span class="mm-r">Impact</span>
                <span class="mm-r">Financial</span>
                <span class="mm-r">Material</span>
            </div>
            @foreach ($topics as $key => $t)
                @php
                    $ink = $pillarInk[$t['pillar'] ?? ''] ?? '#8b9199';
                    $isMaterial = (bool) $t['is_material'];
                @endphp
                <div class="mm-table__row">
                    <span class="mm-topic">
                        <i style="background:{{ $ink }}"></i>
                        <span>{{ $t['label'] }}</span>
                    </span>
                    <span class="mm-r mm-gri">{{ $t['gri'] }}</span>
                    <span class="mm-r mm-lvl">{{ $t['impact_materiality'] ? ucfirst($t['impact_materiality']) : '—' }}</span>
                    <span class="mm-r mm-lvl">{{ $t['financial_materiality'] ? ucfirst($t['financial_materiality']) : '—' }}</span>
                    <span class="mm-r">
                        <span class="mm-flag {{ $isMaterial ? 'is-yes' : 'is-no' }}">{{ $isMaterial ? 'Yes' : 'No' }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
