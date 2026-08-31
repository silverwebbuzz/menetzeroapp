{{--
    MENetZero 2.0 — Double materiality matrix (Phase 5.5, Governance).

    FORM CONTRACT: a single bulk-edit form posting a nested array —
    topics[{key}][impact_materiality], [financial_materiality], [is_material].
    MaterialityMatrixController::update() reads $request->input('topics', [])
    and hands it to DisclosureService::syncMaterialityMatrix().

    The {key} in each name is the topic key from the controller's $topics
    array. Renaming or re-indexing it would silently write to the wrong topic,
    so the loop key is used verbatim.

    Controller data: $topics $levels $fiscalYear
--}}
@extends('layouts.app')

@section('title', 'Materiality Matrix')
@section('page-title', 'Materiality Assessment Matrix')

@section('content')
<div class="mnz-stack" data-pillar="g">

    {{-- Framework tab strip removed: this page is a register owned by

         its pillar, not a section of a framework. The lineage line names

         the reports that read it instead. --}}

    @include('layouts.partials.register-lineage')

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · GRI 3 / IFRS S1</div>
            <h1>Double materiality</h1>
            <p class="mnz-lead">
                Impact and financial materiality for {{ $fiscalYear }}. Topics marked
                medium or high on either axis are flagged as material.
            </p>
        </div>
        <div class="mnz-pagehead__actions">
            <a href="{{ route('disclosures.s1.material-topics', ['fiscal_year' => $fiscalYear]) }}"
               class="mnz-btn mnz-btn--ghost">Material topics list</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mnz-panel" style="border-color:var(--ok-line);background:var(--ok-tint)">
            <div class="mnz-panel__body" style="color:var(--ok)">{{ session('success') }}</div>
        </div>
    @endif

    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <x-field-help key="esg_depth.materiality.intro" class="mb-4" />

            <form method="POST" action="{{ route('disclosures.materiality-matrix.update', ['fiscal_year' => $fiscalYear]) }}">
                @csrf

                <div class="mnz-table" style="--cols: 2fr 100px 1fr 1fr 90px">
                    <div class="mnz-table__head">
                        <span>Topic</span><span>GRI</span>
                        <span>Impact materiality</span><span>Financial materiality</span>
                        <span class="t-r">Material</span>
                    </div>

                    @foreach ($topics as $key => $topic)
                        <div class="mnz-table__row">
                            <span class="t-name">{{ $topic['label'] }}</span>
                            <span class="mnz-mono mnz-muted">{{ $topic['gri'] }}</span>

                            <span>
                                <select class="mnz-select" name="topics[{{ $key }}][impact_materiality]"
                                        aria-label="Impact materiality for {{ $topic['label'] }}">
                                    <option value="">—</option>
                                    @foreach ($levels as $val => $label)
                                        <option value="{{ $val }}" @selected($topic['impact_materiality'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </span>

                            <span>
                                <select class="mnz-select" name="topics[{{ $key }}][financial_materiality]"
                                        aria-label="Financial materiality for {{ $topic['label'] }}">
                                    <option value="">—</option>
                                    @foreach ($levels as $val => $label)
                                        <option value="{{ $val }}" @selected($topic['financial_materiality'] === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </span>

                            <span class="t-r">
                                <input type="checkbox" name="topics[{{ $key }}][is_material]" value="1"
                                       @checked($topic['is_material'])
                                       aria-label="{{ $topic['label'] }} is material">
                            </span>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:16px">
                    <button type="submit" class="mnz-btn mnz-btn--primary">Save materiality matrix</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Double-materiality view. Same structure and classes as the old
         theme's, from Menetzero-Redesign/Internal.dc.html -- CSS-positioned
         dots with inline labels, plus the five-column topics table. Sharing
         the .mm-* classes means a topic cannot be plotted differently in the
         two themes. --}}
    @php
        $levels = ['low' => 0, 'medium' => 1, 'high' => 2];
        $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];

        $plotted = [];
        foreach ($topics as $key => $t) {
            $ix = $levels[$t['impact_materiality'] ?: 'low'] ?? 0;
            $fx = $levels[$t['financial_materiality'] ?: 'low'] ?? 0;
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
    @endphp

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
