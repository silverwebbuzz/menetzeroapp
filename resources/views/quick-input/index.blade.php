@extends('layouts.app')

@section('title', 'Quick Input Entries - MENetZero')
@section('page-title', 'Quick Input Entries')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Quick Input Entries</h1>
        <p class="mt-2 text-gray-600">View and manage your emission data entries.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Summary Cards -->
    @if(isset($summary))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Entries</h3>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary->total_entries ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total CO2e</h3>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary->total_co2e ?? 0, 2) }} kg</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Scope 1</h3>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary->scope_1_co2e ?? 0, 2) }} kg</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Scope 2</h3>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary->scope_2_co2e ?? 0, 2) }} kg</p>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{ route('quick-input.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="scope" class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                <select name="scope" id="scope" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">All Scopes</option>
                    <option value="Scope 1" {{ request('scope') == 'Scope 1' ? 'selected' : '' }}>Scope 1</option>
                    <option value="Scope 2" {{ request('scope') == 'Scope 2' ? 'selected' : '' }}>Scope 2</option>
                    <option value="Scope 3" {{ request('scope') == 'Scope 3' ? 'selected' : '' }}>Scope 3</option>
                </select>
            </div>
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <select name="location_id" id="location_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <input type="number" name="fiscal_year" id="fiscal_year" value="{{ request('fiscal_year') }}" min="2000" max="2100" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">Filter</button>
            </div>
        </form>
    </div>

    <!-- Entries Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CO2e (kg)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scope</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $entry->emissionSource->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->measurement->location->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($entry->quantity, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->unit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ number_format($entry->calculated_co2e, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ $entry->scope }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No entries found. <a href="{{ route('quick-input.show', ['scope' => 1, 'slug' => 'natural-gas']) }}" class="text-purple-600 hover:text-purple-800">Add your first entry</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $entries->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

