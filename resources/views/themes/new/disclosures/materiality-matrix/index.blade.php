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

    {{-- Double-materiality plot: numbered dots plus a key.

         Names are NOT drawn on the plot: topic labels run long and several
         topics share a cell, so inline text overlapped and truncated. The
         number keeps the plot legible; the key carries name, GRI code and
         both scores.

         Same geometry as the old theme's, so a topic cannot appear in
         different places in the two themes. --}}
    <div class="mnz-panel mnz-seam">
        <div class="mnz-panel__head">
            <h3 style="font-size:14px;font-weight:600;margin:0">Materiality matrix</h3>
        </div>
        <div class="mnz-panel__body">
            @php
                $levels = ['low' => 0, 'medium' => 1, 'high' => 2];
                $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];

                $W = 460; $H = 400; $padL = 58; $padR = 20; $padT = 18; $padB = 48;
                $plotW = $W - $padL - $padR;
                $plotH = $H - $padT - $padB;

                $plotted = [];
                $n = 0;
                foreach ($topics as $key => $t) {
                    $n++;
                    $ix = $levels[$t['impact_materiality'] ?: 'low'] ?? 0;
                    $fx = $levels[$t['financial_materiality'] ?: 'low'] ?? 0;
                    $seed = crc32($key);
                    $jx = (($seed % 44) - 22) / 100;
                    $jy = ((($seed >> 8) % 44) - 22) / 100;

                    $plotted[] = [
                        'n' => $n,
                        'label' => $t['label'],
                        'gri' => $t['gri'],
                        'impact' => $t['impact_materiality'] ?: 'low',
                        'financial' => $t['financial_materiality'] ?: 'low',
                        'material' => (bool) $t['is_material'],
                        'ink' => $pillarInk[$t['pillar'] ?? ''] ?? '#8b9199',
                        'x' => $padL + (($fx + 0.5 + $jx) / 3) * $plotW,
                        'y' => $padT + $plotH - (($ix + 0.5 + $jy) / 3) * $plotH,
                    ];
                }
            @endphp

            <div class="mm-layout">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" class="mm-plot"
                     role="img" aria-label="Double materiality matrix for {{ $fiscalYear }}">
                    @foreach ([0, 1, 2, 3] as $i)
                        @php
                            $gx = $padL + ($i / 3) * $plotW;
                            $gy = $padT + ($i / 3) * $plotH;
                        @endphp
                        <line x1="{{ round($gx, 1) }}" y1="{{ $padT }}" x2="{{ round($gx, 1) }}" y2="{{ $padT + $plotH }}"
                              stroke="var(--line)" stroke-width="1"/>
                        <line x1="{{ $padL }}" y1="{{ round($gy, 1) }}" x2="{{ $padL + $plotW }}" y2="{{ round($gy, 1) }}"
                              stroke="var(--line)" stroke-width="1"/>
                    @endforeach

                    @foreach (['Low', 'Medium', 'High'] as $i => $lbl)
                        <text x="{{ round($padL + (($i + 0.5) / 3) * $plotW, 1) }}" y="{{ $H - 26 }}"
                              text-anchor="middle" font-size="11" fill="var(--ink-3)">{{ $lbl }}</text>
                        <text x="{{ $padL - 12 }}" y="{{ round($padT + $plotH - (($i + 0.5) / 3) * $plotH + 4, 1) }}"
                              text-anchor="end" font-size="11" fill="var(--ink-3)">{{ $lbl }}</text>
                    @endforeach

                    <text x="{{ round($padL + $plotW / 2, 1) }}" y="{{ $H - 6 }}" text-anchor="middle"
                          font-size="9.5" fill="var(--ink-4)" letter-spacing="1.2">FINANCIAL &rarr;</text>
                    <text x="13" y="{{ round($padT + $plotH / 2, 1) }}" text-anchor="middle"
                          font-size="9.5" fill="var(--ink-4)" letter-spacing="1.2"
                          transform="rotate(-90 13 {{ round($padT + $plotH / 2, 1) }})">IMPACT &uarr;</text>

                    @foreach ($plotted as $pt)
                        <circle cx="{{ round($pt['x'], 1) }}" cy="{{ round($pt['y'], 1) }}" r="11"
                                fill="{{ $pt['material'] ? $pt['ink'] : 'var(--surface)' }}"
                                stroke="{{ $pt['ink'] }}" stroke-width="2"/>
                        <text x="{{ round($pt['x'], 1) }}" y="{{ round($pt['y'] + 4, 1) }}"
                              text-anchor="middle" font-size="11" font-weight="600"
                              fill="{{ $pt['material'] ? '#fff' : $pt['ink'] }}">{{ $pt['n'] }}</text>
                    @endforeach
                </svg>

                <ol class="mm-key">
                    @foreach ($plotted as $pt)
                        <li>
                            <span class="mm-key__n" style="background:{{ $pt['material'] ? $pt['ink'] : 'var(--surface)' }};
                                  border-color:{{ $pt['ink'] }};
                                  color:{{ $pt['material'] ? '#fff' : $pt['ink'] }}">{{ $pt['n'] }}</span>
                            <span class="mm-key__label">
                                {{ $pt['label'] }}
                                <span class="mm-key__gri">{{ $pt['gri'] }}</span>
                            </span>
                            <span class="mm-key__score">{{ ucfirst($pt['impact'][0]) }}/{{ ucfirst($pt['financial'][0]) }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="mm-legend">
                <span><i style="background:#0f7a4a"></i>Environmental</span>
                <span><i style="background:#1a6c9e"></i>Social</span>
                <span><i style="background:#5b5aa8"></i>Governance</span>
                <span><i style="background:var(--surface);border:2px solid #8b9199"></i>Not flagged material</span>
            </div>
        </div>
    </div>

</div>
@endsection
