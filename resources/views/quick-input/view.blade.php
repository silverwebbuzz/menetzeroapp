@extends('layouts.app')

@section('title', 'Entry Details - Quick Input - MENetZero')
@section('page-title', 'Entry Details')

@section('content')
<div class="w-full">
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
                    <dt class="text-sm font-medium text-gray-500 w-1/3">Total Emissions</dt>
                    <dd class="text-2xl font-bold text-purple-600 flex-1">{{ co2e_t($entry->calculated_co2e, 4) }} <span class="text-sm font-medium text-gray-500">tCO₂e</span></dd>
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

    <!-- Additional Data & Evidence -->
    @php
        $additionalData = decode_json_field($entry->additional_data ?? []);
        $evidenceLink = $additionalData['evidence_link'] ?? $additionalData['link'] ?? null;
        unset($additionalData['evidence_link'], $additionalData['link']);

        if ($entry->unit) {
            $additionalData['Unit'] = $entry->unit;
        }
        if ($entry->fuel_category) {
            $additionalData['Fuel Category'] = $entry->fuel_category;
        } elseif (isset($additionalData['fuel_category'])) {
            $additionalData['Fuel Category'] = $additionalData['fuel_category'];
            unset($additionalData['fuel_category']);
        }
        if ($entry->fuel_type) {
            $additionalData['Fuel Type'] = $entry->fuel_type;
        }

        $hasEvidence = $entry->entry_date || !empty($entry->supporting_docs) || $evidenceLink || $entry->notes;
        $hasAdditionalFields = is_array($additionalData) && count(array_filter($additionalData, fn ($v) => $v !== null && $v !== '')) > 0;
    @endphp
    @if($hasAdditionalFields || $hasEvidence)
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden entry-detail-card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Additional Data</h2>
            <p class="text-sm text-gray-500 mt-0.5">Optional context, evidence, and notes</p>
        </div>
        <div class="px-6 py-4 space-y-6">
            @if($hasAdditionalFields)
                <dl class="space-y-4">
                    @foreach($additionalData as $key => $value)
                        @if($value !== null && $value !== '')
                            <div class="flex items-center border-b border-gray-100 pb-3">
                                <dt class="text-sm font-medium text-gray-500 w-1/3">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="text-sm text-gray-900 flex-1">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif

            @if($hasEvidence)
                <div class="{{ $hasAdditionalFields ? 'pt-4 border-t border-gray-100' : '' }}">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Evidence &amp; notes</h3>
                    <dl class="space-y-4">
                        @if($entry->entry_date)
                        <div class="flex items-center border-b border-gray-100 pb-3">
                            <dt class="text-sm font-medium text-gray-500 w-1/3">Activity / bill date</dt>
                            <dd class="text-sm text-gray-900 flex-1">{{ $entry->entry_date->format('Y-m-d') }}</dd>
                        </div>
                        @endif
                        @if($evidenceLink)
                        <div class="flex items-center border-b border-gray-100 pb-3">
                            <dt class="text-sm font-medium text-gray-500 w-1/3">Link</dt>
                            <dd class="text-sm flex-1">
                                <a href="{{ $evidenceLink }}" target="_blank" rel="noopener noreferrer"
                                   class="text-emerald-700 hover:text-emerald-900 hover:underline break-all">{{ $evidenceLink }}</a>
                            </dd>
                        </div>
                        @endif
                        @if(!empty($entry->supporting_docs))
                        <div class="flex items-start border-b border-gray-100 pb-3">
                            <dt class="text-sm font-medium text-gray-500 w-1/3">Supporting documents</dt>
                            <dd class="text-sm text-gray-900 flex-1">
                                <ul class="space-y-1">
                                    @foreach($entry->supporting_docs as $index => $doc)
                                        <li>
                                            <a href="{{ route('quick-input.documents.download', [$entry->id, $index]) }}"
                                               class="text-emerald-700 hover:text-emerald-900 hover:underline">
                                                {{ $doc['filename'] ?? 'Document' }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                        @endif
                        @if($entry->notes)
                        <div class="flex items-start">
                            <dt class="text-sm font-medium text-gray-500 w-1/3">Comments</dt>
                            <dd class="text-sm text-gray-900 flex-1 whitespace-pre-wrap">{{ $entry->notes }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            @endif
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

