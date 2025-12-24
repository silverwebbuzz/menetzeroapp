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
            <dl class="space-y-4">
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Emission Source</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->emissionSource->name ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Location</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->measurement->location->name ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Year</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->measurement->fiscal_year ?? 'N/A' }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Entry Date</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Quantity</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ number_format($entry->quantity, 4) }} {{ $entry->unit }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Scope</dt>
                    <dd class="flex-1">
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
            <dl class="space-y-4">
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Total CO2e</dt>
                    <dd class="text-2xl font-bold text-purple-600 flex-1">{{ number_format($entry->calculated_co2e, 2) }} kg</dd>
                </div>
                @if($entry->co2_emissions !== null)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">CO2 Emissions</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ number_format($entry->co2_emissions, 6) }} kg</dd>
                </div>
                @endif
                @if($entry->ch4_emissions !== null)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">CH4 Emissions</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ number_format($entry->ch4_emissions, 6) }} kg</dd>
                </div>
                @endif
                @if($entry->n2o_emissions !== null)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">N2O Emissions</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ number_format($entry->n2o_emissions, 6) }} kg</dd>
                </div>
                @endif
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">GWP Version Used</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->gwp_version_used ?? 'AR6' }}</dd>
                </div>
                @if($entry->emissionFactor)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Calculation Method</dt>
                    <dd class="text-sm text-gray-900 flex-1">
                        {{ $entry->emissionFactor->calculation_method ?? $entry->calculation_method ?? 'N/A' }}
                    </dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Emission Factor</dt>
                    <dd class="text-sm text-gray-900 flex-1">
                        {{ number_format($entry->emissionFactor->factor_value ?? 0, 6) }} 
                        {{ $entry->emissionFactor->unit ?? $entry->unit ?? '' }} 
                        @if($entry->emissionFactor->source_reference)
                            <span class="text-gray-500">({{ $entry->emissionFactor->source_reference }})</span>
                        @endif
                    </dd>
                </div>
                @if($entry->emissionFactor->source_standard)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Source Standard</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->emissionFactor->source_standard }}</dd>
                </div>
                @endif
                @if($entry->emissionFactor->region)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Region</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->emissionFactor->region }}</dd>
                </div>
                @endif
                @elseif($entry->emission_factor_id)
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Calculation Method</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->calculation_method ?? 'N/A' }}</dd>
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
                <dl class="space-y-4">
                    @foreach($additionalData as $key => $value)
                        <div class="flex items-center border-b border-gray-100 pb-3">
                            <dt class="text-sm font-medium text-gray-500 w-1/3">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
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
            <dl class="space-y-4">
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Created At</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div class="flex items-center border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Last Updated</dt>
                    <dd class="text-sm text-gray-900 flex-1">{{ $entry->updated_at->format('Y-m-d H:i:s') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

