@extends('layouts.app')

@section('title', 'Entry Details - Quick Input - MENetZero')
@section('page-title', 'Entry Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Entry Details</h1>
            <p class="mt-2 text-gray-600">View detailed information about this emission entry.</p>
        </div>
        <div class="flex space-x-3">
            @php
                $scopeNumber = str_replace('Scope ', '', $entry->scope);
                $slug = $entry->emissionSource->quick_input_slug ?? '';
            @endphp
            <a href="{{ route('quick-input.show', ['scope' => $scopeNumber, 'slug' => $slug, 'edit' => $entry->id, 'location_id' => $entry->measurement->location_id, 'fiscal_year' => $entry->measurement->fiscal_year]) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Edit
            </a>
            <form action="{{ route('quick-input.destroy', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Delete
                </button>
            </form>
            <a href="{{ route('quick-input.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <!-- Entry Details Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Emission Source</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->emissionSource->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Location</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->measurement->location->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Year</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->measurement->fiscal_year ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Entry Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Quantity</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($entry->quantity, 4) }} {{ $entry->unit }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Scope</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ $entry->scope }}</span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Calculation Details Card -->
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Calculation Details</h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total CO2e</dt>
                    <dd class="mt-1 text-2xl font-bold text-purple-600">{{ number_format($entry->calculated_co2e, 2) }} kg</dd>
                </div>
                @if($entry->co2_emissions !== null)
                <div>
                    <dt class="text-sm font-medium text-gray-500">CO2 Emissions</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($entry->co2_emissions, 6) }} kg</dd>
                </div>
                @endif
                @if($entry->ch4_emissions !== null)
                <div>
                    <dt class="text-sm font-medium text-gray-500">CH4 Emissions</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($entry->ch4_emissions, 6) }} kg</dd>
                </div>
                @endif
                @if($entry->n2o_emissions !== null)
                <div>
                    <dt class="text-sm font-medium text-gray-500">N2O Emissions</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($entry->n2o_emissions, 6) }} kg</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">GWP Version Used</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->gwp_version_used ?? 'AR6' }}</dd>
                </div>
                @if($entry->emissionFactor)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Calculation Method</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $entry->emissionFactor->calculation_method ?? $entry->calculation_method ?? 'N/A' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Emission Factor</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ number_format($entry->emissionFactor->factor_value ?? 0, 6) }} 
                        {{ $entry->emissionFactor->unit ?? $entry->unit ?? '' }} 
                        @if($entry->emissionFactor->source_reference)
                            <span class="text-gray-500">({{ $entry->emissionFactor->source_reference }})</span>
                        @endif
                    </dd>
                </div>
                @if($entry->emissionFactor->source_standard)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Source Standard</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->emissionFactor->source_standard }}</dd>
                </div>
                @endif
                @if($entry->emissionFactor->region)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Region</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->emissionFactor->region }}</dd>
                </div>
                @endif
                @elseif($entry->emission_factor_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Calculation Method</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $entry->calculation_method ?? 'N/A' }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Additional Data Card -->
    @if($entry->additional_data)
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Additional Information</h2>
        </div>
        <div class="px-6 py-4">
            @php
                $additionalData = is_string($entry->additional_data) ? json_decode($entry->additional_data, true) : $entry->additional_data;
            @endphp
            @if(is_array($additionalData) && count($additionalData) > 0)
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($additionalData as $key => $value)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
    @endif

    <!-- Notes Card -->
    @if($entry->notes)
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
        </div>
        <div class="px-6 py-4">
            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $entry->notes }}</p>
        </div>
    </div>
    @endif

    <!-- Metadata -->
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Metadata</h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-gray-900">{{ $entry->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-gray-900">{{ $entry->updated_at->format('Y-m-d H:i:s') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

