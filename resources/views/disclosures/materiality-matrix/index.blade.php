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
                    <a href="{{ route('disclosures.materiality-matrix.snapshot', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary ml-2">View matrix</a>
                    <a href="{{ route('disclosures.s1.material-topics', ['fiscal_year' => $fiscalYear]) }}" class="btn btn-secondary ml-2">Material topics list</a>
                </div>
            </form>
        </div>
    </div>

    {{-- The plot lives on the READ-ONLY snapshot (Overview > Materiality),
         not here. This page is the scoring form: showing the same matrix
         twice would mean two places for one set of numbers to disagree. --}}
</div>
@endsection
