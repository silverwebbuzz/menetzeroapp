@extends('layouts.app')

@section('title', 'Scope 1 & 2 Help Guide - MENetZero')
@section('page-title', 'Scope 1 & 2 Help Guide')

@section('content')
<div class="w-full max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('quick-input.index') }}" class="text-emerald-700 hover:text-emerald-900 text-sm font-medium inline-flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Input Data
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $intro['title'] }}</h1>
        <p class="mt-3 text-gray-600 text-lg leading-relaxed">{{ $intro['summary'] }}</p>
    </div>

    {{-- Quick start for typical UAE office --}}
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-bold text-emerald-900 mb-3">What most UAE offices need (start here)</h2>
        <p class="text-emerald-800 text-sm mb-4">If this is your first time, you probably only need these documents:</p>
        <ul class="space-y-2">
            @foreach($intro['typical_office'] as $item)
                <li class="flex items-start text-sm text-emerald-900">
                    <svg class="w-5 h-5 text-emerald-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $item }}
                </li>
            @endforeach
        </ul>
        @if($locations->isNotEmpty())
            <p class="mt-4 text-sm text-emerald-800"><strong>Your locations in MENetZero:</strong> {{ $locations->join(', ') }}</p>
        @endif
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('quick-input.bulk-import.template', ['format' => 'xlsx']) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                Download Excel template
            </a>
            <a href="{{ route('quick-input.index') }}#bulk-import" class="inline-flex items-center px-4 py-2 bg-white border border-emerald-300 text-emerald-800 text-sm font-medium rounded-lg hover:bg-emerald-100">
                Go to bulk upload
            </a>
        </div>
    </div>

    {{-- Spreadsheet columns explained --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 mb-8 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Spreadsheet columns — what each field means</h2>
            <p class="text-sm text-gray-600 mt-1">Every column in the bulk import template explained in plain language.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($columns as $col)
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:gap-4">
                    <div class="sm:w-40 flex-shrink-0">
                        <code class="text-sm font-semibold text-emerald-800 bg-emerald-50 px-2 py-1 rounded">{{ $col['column'] }}</code>
                        @if($col['required'] === true)
                            <span class="ml-2 text-xs text-red-600 font-medium">Required</span>
                        @elseif($col['required'] === 'Depends')
                            <span class="ml-2 text-xs text-amber-600 font-medium">Sometimes</span>
                        @else
                            <span class="ml-2 text-xs text-gray-400">Optional</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700 mt-2 sm:mt-0">{{ $col['explain'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Per-category guides --}}
    <h2 class="text-2xl font-bold text-gray-900 mb-4">Guide by activity type</h2>
    <p class="text-gray-600 mb-6">Click each section to see: what it is, valid units, where to find the number on UAE documents, and a filled example.</p>

    <div class="space-y-4 mb-10">
        @foreach($categories as $cat)
            <details class="group bg-white rounded-xl shadow border border-gray-200 overflow-hidden" {{ $loop->first ? 'open' : '' }}>
                <summary class="px-6 py-4 cursor-pointer list-none flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">S{{ $cat['scope'] }}</span>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $cat['title'] }}</h3>
                            <p class="text-sm text-gray-500">category = <code class="text-emerald-700">{{ $cat['category_value'] }}</code></p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>

                <div class="px-6 pb-6 border-t border-gray-100 pt-4 space-y-5">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-1">In plain English</h4>
                        <p class="text-gray-700">{{ $cat['plain'] }}</p>
                        <p class="text-sm text-gray-500 mt-2"><strong>Who needs this?</strong> {{ $cat['who_needs'] }}</p>
                    </div>

                    {{-- Valid unit combinations --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Valid unit combinations</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Unit</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">When to use</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Example quantity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($cat['units'] as $u)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-emerald-800">{{ $u['unit'] }}</td>
                                            <td class="px-4 py-2 text-gray-700">{{ $u['when'] }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $u['example'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(!empty($cat['sub_types']))
                            <h5 class="text-sm font-medium text-gray-800 mt-4 mb-2">sub_type options (copy exactly into spreadsheet)</h5>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left font-semibold text-gray-700">sub_type value</th>
                                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Use when</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($cat['sub_types'] as $st)
                                            <tr>
                                                <td class="px-4 py-2 font-mono text-emerald-800 text-xs">{{ $st['value'] }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ $st['use_when'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-600 mt-2"><strong>sub_type:</strong> {{ $cat['sub_type'] }}</p>
                        @endif
                    </div>

                    {{-- Where to find in UAE --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Where to find this data in UAE</h4>
                        <div class="space-y-3">
                            @foreach($cat['where_uae'] as $src)
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                    <p class="font-semibold text-blue-900">{{ $src['source'] }}</p>
                                    <p class="text-sm text-blue-800 mt-1"><strong>Look for:</strong> {{ $src['look_for'] }}</p>
                                    <p class="text-sm text-blue-700 mt-1"><strong>In spreadsheet:</strong> {{ $src['field'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Example row --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Example row</h4>
                        <div class="bg-gray-50 rounded-lg p-4 font-mono text-xs overflow-x-auto">
                            @foreach($cat['example_row'] as $key => $val)
                                <div class="flex gap-2 py-0.5">
                                    <span class="text-gray-500 w-28 flex-shrink-0">{{ $key }}:</span>
                                    <span class="text-gray-900">{{ $val ?: '(blank)' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Common mistakes --}}
                    @if(!empty($cat['mistakes']))
                        <div>
                            <h4 class="text-sm font-semibold text-red-800 uppercase tracking-wide mb-2">Common mistakes to avoid</h4>
                            <ul class="list-disc list-inside text-sm text-red-900 space-y-1">
                                @foreach($cat['mistakes'] as $m)
                                    <li>{{ $m }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    {{-- Tips footer --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8">
        <h3 class="font-bold text-gray-900 mb-3">General tips</h3>
        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
            @foreach($intro['tips'] as $tip)
                <li>{{ $tip }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
