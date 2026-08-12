@extends('layouts.app')

@section('title', 'Scope 3 Help Guide - MENetZero')
@section('page-title', 'Scope 3 Help Guide')

@section('content')
<div class="w-full">
    <div class="mb-6">
        <a href="{{ route('quick-input.index') }}" class="text-emerald-700 hover:text-emerald-900 text-sm font-medium inline-flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Input Data
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $intro['title'] }}</h1>
        <p class="mt-3 text-gray-600 text-lg leading-relaxed">{{ $intro['summary'] }}</p>
    </div>

    {{-- The single most misunderstood point about Scope 3 --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-bold text-amber-900 mb-2">One total per category — not one row per person</h2>
        <p class="text-amber-900 text-sm leading-relaxed">
            If 200 staff commute and your team flies 80 trips a year, you do <strong>not</strong> enter 280 rows.
            You enter <strong>one</strong> row for commuting and <strong>one</strong> row per flight class.
            The workbook's <strong>Calc: Commuting</strong> and <strong>Calc: Flights</strong> sheets let you keep the
            per-person detail and add it up for you — those sheets are not uploaded, they just do the maths.
        </p>
    </div>

    {{-- Where to start --}}
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-bold text-emerald-900 mb-3">Start with these five</h2>
        <p class="text-emerald-800 text-sm mb-4">You do not need all 15 categories. These are the ones most UAE businesses can fill quickly:</p>
        <ul class="space-y-2">
            @foreach($intro['start_here'] as $item)
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
            <a href="{{ route('quick-input.scope3-bulk-import.template', ['format' => 'xlsx']) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                Download Excel template
            </a>
            <a href="{{ route('quick-input.index') }}#scope3-bulk-import" class="inline-flex items-center px-4 py-2 bg-white border border-emerald-300 text-emerald-800 text-sm font-medium rounded-lg hover:bg-emerald-100">
                Go to bulk upload
            </a>
        </div>
    </div>

    {{-- Tips --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 mb-8 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Before you begin</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($intro['tips'] as $tip)
                <li class="px-6 py-3 text-sm text-gray-700">{{ $tip }}</li>
            @endforeach
        </ul>
    </div>

    {{-- Columns --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 mb-8 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Spreadsheet columns — what each field means</h2>
            <p class="text-sm text-gray-600 mt-1">Every column in the Scope 3 import template, explained.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($columns as $col)
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:gap-4">
                    <div class="sm:w-44 flex-shrink-0 mb-2 sm:mb-0">
                        <code class="text-sm font-semibold text-emerald-800 bg-emerald-50 px-2 py-1 rounded">{{ $col['column'] }}</code>
                        @if($col['required'])
                            <span class="ml-2 text-xs text-red-600 font-medium">Required</span>
                        @else
                            <span class="ml-2 text-xs text-gray-400">Optional</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $col['plain'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $col['detail'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Units --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 mb-8 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Units — the most common cause of rejected rows</h2>
            <p class="text-sm text-gray-600 mt-1">Each activity type accepts only specific units. Copy them exactly from the Reference sheet.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold text-gray-700">Unit</th>
                        <th class="text-left px-6 py-3 font-semibold text-gray-700">What it means</th>
                        <th class="text-left px-6 py-3 font-semibold text-gray-700">Watch out for</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($units as $unit)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap"><code class="text-emerald-800 bg-emerald-50 px-2 py-1 rounded font-semibold">{{ $unit['unit'] }}</code></td>
                            <td class="px-6 py-3 text-gray-900">{{ $unit['means'] }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $unit['watch'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Categories --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">The 15 categories</h2>
        <p class="text-gray-600 mb-6">Every valid activity type and unit, grouped by category. This is the same list as the Reference sheet in the template.</p>

        <div class="space-y-6">
            @foreach($categories as $cat)
                <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-lg font-bold text-gray-900">{{ $cat['title'] }}</h3>
                            <code class="text-xs text-gray-500">category = {{ $cat['number'] }} or {{ $cat['slug'] }}</code>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ $cat['plain'] }}</p>
                        @if($cat['who_needs'])
                            <p class="text-xs text-gray-500 mt-1"><strong>Who needs it:</strong> {{ $cat['who_needs'] }}</p>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-6 py-2 font-semibold text-gray-700">activity_type (copy exactly)</th>
                                    <th class="text-left px-6 py-2 font-semibold text-gray-700">unit</th>
                                    <th class="text-left px-6 py-2 font-semibold text-gray-700">Where to find the number</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($cat['activities'] as $activity)
                                    <tr>
                                        <td class="px-6 py-2 whitespace-nowrap"><code class="text-gray-900 font-medium">{{ $activity['activity_type'] }}</code></td>
                                        <td class="px-6 py-2 whitespace-nowrap"><code class="text-emerald-800">{{ $activity['unit'] }}</code></td>
                                        <td class="px-6 py-2 text-gray-600">{{ $activity['where'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-2">Still stuck?</h2>
        <p class="text-sm text-gray-600 mb-4">Send us the bill or report you are trying to enter and we will tell you which category and unit to use.</p>
        <a href="{{ route('client.support') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
            Contact support
        </a>
    </div>
</div>
@endsection
