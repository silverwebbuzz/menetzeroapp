@extends('layouts.app')

@section('title', 'Materiality Matrix')
@section('page-title', 'Materiality Assessment Matrix')

@section('content')
<div class="w-full">
    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

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

    <div class="card">
        <div class="card-header"><h3 class="card-title">Visual matrix (material topics)</h3></div>
        <div class="card-body">
            <div class="grid grid-cols-4 gap-1 max-w-lg text-center text-xs">
                <div></div>
                <div class="font-medium text-gray-500">Fin. Low</div>
                <div class="font-medium text-gray-500">Fin. Med</div>
                <div class="font-medium text-gray-500">Fin. High</div>
                @foreach(['high' => 'Impact High', 'medium' => 'Impact Med', 'low' => 'Impact Low'] as $impact => $impactLabel)
                    <div class="font-medium text-gray-500 text-right pr-2 py-2">{{ $impactLabel }}</div>
                    @foreach(['low', 'medium', 'high'] as $fin)
                        @php
                            $cellTopics = collect($topics)->filter(fn ($t) => ($t['impact_materiality'] ?: 'low') === $impact && ($t['financial_materiality'] ?: 'low') === $fin && $t['is_material']);
                        @endphp
                        <div class="border border-gray-100 rounded p-2 min-h-[48px] {{ $cellTopics->isNotEmpty() ? 'bg-brand-50' : 'bg-gray-50' }}">
                            @foreach($cellTopics as $t)
                                <div class="text-[10px] leading-tight">{{ $t['label'] }}</div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
