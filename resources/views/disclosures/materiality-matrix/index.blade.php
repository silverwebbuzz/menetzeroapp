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

    {{-- Double-materiality scatter: impact (GRI 3) up, financial (IFRS S1)
         across. Replaces the 3x3 label grid -- same data, but a plot shows
         WHERE a topic sits relative to the others, which a grid of cells does
         not.

         ALL topics are plotted, not just material ones. The old grid filtered
         to is_material, which hid exactly the topics a reviewer needs to see:
         the ones scored high but not yet flagged.

         Scores are three discrete levels, so points land on a 3x3 lattice.
         Each is nudged deterministically within its cell (by topic key) so
         co-located topics stay readable and do not jump between renders. --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Materiality matrix</h3>
                <p class="card-subtitle">Impact on the world (GRI 3) against financial effect on the business (IFRS S1).</p>
            </div>
        </div>
        <div class="card-body">
            @php
                $levels = ['low' => 0, 'medium' => 1, 'high' => 2];
                $pillarInk = ['e' => '#0f7a4a', 's' => '#1a6c9e', 'g' => '#5b5aa8'];

                $W = 640; $H = 380; $padL = 56; $padR = 24; $padT = 20; $padB = 44;
                $plotW = $W - $padL - $padR;
                $plotH = $H - $padT - $padB;

                $plotted = [];
                foreach ($topics as $key => $t) {
                    $ix = $levels[$t['impact_materiality'] ?: 'low'] ?? 0;
                    $fx = $levels[$t['financial_materiality'] ?: 'low'] ?? 0;

                    // Deterministic nudge from the topic key: same layout every
                    // render, no overlap for topics sharing a cell.
                    $seed = crc32($key);
                    $jx = (($seed % 40) - 20) / 100;
                    $jy = ((($seed >> 8) % 40) - 20) / 100;

                    $plotted[] = [
                        'label' => $t['label'],
                        'material' => (bool) $t['is_material'],
                        'ink' => $pillarInk[$t['pillar'] ?? ''] ?? '#8b9199',
                        'x' => $padL + (($fx + 0.5 + $jx) / 3) * $plotW,
                        'y' => $padT + $plotH - (($ix + 0.5 + $jy) / 3) * $plotH,
                    ];
                }
            @endphp

            <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" style="max-width:{{ $W }}px;height:auto"
                 role="img" aria-label="Double materiality matrix for {{ $fiscalYear }}">
                @foreach ([0, 1, 2, 3] as $i)
                    @php
                        $gx = $padL + ($i / 3) * $plotW;
                        $gy = $padT + ($i / 3) * $plotH;
                    @endphp
                    <line x1="{{ round($gx, 1) }}" y1="{{ $padT }}" x2="{{ round($gx, 1) }}" y2="{{ $padT + $plotH }}"
                          stroke="#e5e6e3" stroke-width="1"/>
                    <line x1="{{ $padL }}" y1="{{ round($gy, 1) }}" x2="{{ $padL + $plotW }}" y2="{{ round($gy, 1) }}"
                          stroke="#e5e6e3" stroke-width="1"/>
                @endforeach

                @foreach (['Low', 'Medium', 'High'] as $i => $lbl)
                    <text x="{{ round($padL + (($i + 0.5) / 3) * $plotW, 1) }}" y="{{ $H - 22 }}"
                          text-anchor="middle" font-size="11" fill="#8b9199">{{ $lbl }}</text>
                    <text x="{{ $padL - 10 }}" y="{{ round($padT + $plotH - (($i + 0.5) / 3) * $plotH + 4, 1) }}"
                          text-anchor="end" font-size="11" fill="#8b9199">{{ $lbl }}</text>
                @endforeach

                <text x="{{ round($padL + $plotW / 2, 1) }}" y="{{ $H - 4 }}" text-anchor="middle"
                      font-size="10" fill="#a4a9ae" letter-spacing="1">FINANCIAL &rarr;</text>
                <text x="14" y="{{ round($padT + $plotH / 2, 1) }}" text-anchor="middle"
                      font-size="10" fill="#a4a9ae" letter-spacing="1"
                      transform="rotate(-90 14 {{ round($padT + $plotH / 2, 1) }})">IMPACT &uarr;</text>

                @foreach ($plotted as $pt)
                    {{-- A non-material topic is hollow: present in the
                         assessment, but not selected for reporting. --}}
                    <circle cx="{{ round($pt['x'], 1) }}" cy="{{ round($pt['y'], 1) }}" r="5"
                            fill="{{ $pt['material'] ? $pt['ink'] : '#fff' }}"
                            stroke="{{ $pt['ink'] }}" stroke-width="2"/>
                    <text x="{{ round($pt['x'] + 9, 1) }}" y="{{ round($pt['y'] + 4, 1) }}"
                          font-size="11" fill="#14161a">{{ $pt['label'] }}</text>
                @endforeach
            </svg>

            <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5">
                    <span style="width:10px;height:10px;border-radius:999px;background:#0f7a4a;display:inline-block"></span>Environmental
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span style="width:10px;height:10px;border-radius:999px;background:#1a6c9e;display:inline-block"></span>Social
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span style="width:10px;height:10px;border-radius:999px;background:#5b5aa8;display:inline-block"></span>Governance
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span style="width:10px;height:10px;border-radius:999px;border:2px solid #8b9199;display:inline-block"></span>Not flagged material
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
