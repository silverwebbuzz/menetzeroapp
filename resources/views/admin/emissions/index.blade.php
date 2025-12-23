@extends('admin.layouts.app')

@section('title', 'Emission Management | MENetZero')
@section('page-title', 'Emission Management')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('admin.emissions.sources') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Emission Sources</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['sources']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.emissions.factors') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Emission Factors</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['factors']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.emissions.gwp-values') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">GWP Values</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['gwp_values']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.emissions.unit-conversions') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Unit Conversions</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['unit_conversions']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.emissions.industry-labels') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Industry Labels</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['industry_labels']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.emissions.selection-rules') }}" class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Selection Rules</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($stats['selection_rules']) }}</p>
                </div>
                <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
        </a>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('admin.emissions.sources') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage Emission Sources</a>
            <a href="{{ route('admin.emissions.factors') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage Emission Factors</a>
            <a href="{{ route('admin.emissions.gwp-values') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage GWP Values</a>
            <a href="{{ route('admin.emissions.unit-conversions') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage Unit Conversions</a>
            <a href="{{ route('admin.emissions.industry-labels') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage Industry Labels</a>
            <a href="{{ route('admin.emissions.selection-rules') }}" class="text-purple-600 hover:text-purple-800 hover:underline">Manage Selection Rules</a>
        </div>
    </div>
@endsection

