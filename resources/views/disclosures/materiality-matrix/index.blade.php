@extends('layouts.app')

@section('title', 'Materiality Matrix')
@section('page-title', 'Materiality Assessment Matrix')

@section('content')
<div class="w-full">
    {{-- Framework tab strip removed: this page is a register owned by
         its pillar, not a section of a framework. The lineage line names
         the reports that read it instead. --}}
    @include('layouts.partials.register-lineage')

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Double materiality matrix</h3>
            <p class="card-subtitle">GRI 3 / IFRS S1 — impact and financial materiality for {{ $fiscalYear }}. Topics marked medium/high on either axis are flagged as material.</p>
        </div>
        <div class="card-body">
            <x-field-help key="esg_depth.materiality.intro" class="mb-4" />
            <form method="POST" action="{{ route('disclosures.materiality-matrix.update', ['fiscal_year' => $fiscalYear]) }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-4">Topic</th>
                                <th class="py-2 px-2">GRI</th>
                                <th class="py-2 px-2">Impact materiality</th>
                                <th class="py-2 px-2">Financial materiality</th>
                                <th class="py-2 px-2">Material</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topics as $key => $topic)
                                <tr class="border-b border-gray-50">
                                    <td class="py-3 pr-4 font-medium">{{ $topic['label'] }}</td>
                                    <td class="py-3 px-2 text-xs text-gray-500">{{ $topic['gri'] }}</td>
                                    <td class="py-3 px-2">
                                        <select name="topics[{{ $key }}][impact_materiality]" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                                            <option value="">—</option>
                                            @foreach($levels as $val => $label)
                                                <option value="{{ $val }}" @selected($topic['impact_materiality'] === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2">
                                        <select name="topics[{{ $key }}][financial_materiality]" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                                            <option value="">—</option>
                                            @foreach($levels as $val => $label)
                                                <option value="{{ $val }}" @selected($topic['financial_materiality'] === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <input type="checkbox" name="topics[{{ $key }}][is_material]" value="1" @checked($topic['is_material'])>
                                        @php
                                            // The page's own rule: medium or high on EITHER axis
                                            // makes a topic material. GRI 3-1 allows a documented
                                            // departure from it, but the departure has to be
                                            // visible -- otherwise a topic scored High/High can sit
                                            // unflagged with nothing drawing the eye to it.
                                            $scoredMaterial = in_array($topic['impact_materiality'], ['medium', 'high'], true)
                                                || in_array($topic['financial_materiality'], ['medium', 'high'], true);
                                            $mismatch = $scoredMaterial !== (bool) $topic['is_material'];
                                        @endphp
                                        @if($mismatch)
                                            <div class="text-[10px] text-amber-700 mt-1 leading-tight"
                                                 title="Scores and the material flag disagree. GRI 3-1 expects the reasoning to be documented.">
                                                {{ $scoredMaterial ? 'scored material' : 'scored not material' }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">Save materiality matrix</button>
                    <a href="{{ route('disclosures.s1.material-topics', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary ml-2">Material topics list</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Double-materiality view, matching Menetzero-Redesign/Internal.dc.html.

         CSS-POSITIONED DOTS, NOT SVG. The design labels each point inline
         beside its dot, with a white text backing so overlapping labels stay
         readable. Absolute positioning inside a bordered box reproduces that
         exactly; an SVG would need manual text-collision handling.

         Axis rules on LEFT and BOTTOM only (the design's border-left /
         border-bottom), with a 25% background grid.

         ALL topics are plotted. The original grid filtered to is_material,
         hiding exactly the topics worth reviewing -- those scored high but
         not yet flagged. --}}
    @php
        $levels = ['low' => 0, 'medium' => 1, 'high' => 2];
        $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];

        $plotted = [];
        foreach ($topics as $key => $t) {
            $ix = $levels[$t['impact_materiality'] ?: 'low'] ?? 0;
            $fx = $levels[$t['financial_materiality'] ?: 'low'] ?? 0;

            // Deterministic nudge from the topic key: identical on every
            // render, and topics sharing a cell separate rather than stack.
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
