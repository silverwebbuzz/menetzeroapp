@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

@push('styles')
<link rel="stylesheet" href="{{asset('css/quick-input.css?v=20260620')}}">
@endpush

@section('content')
<div class="w-full" style="box-sizing: border-box; max-width: 100%;">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    @if($editEntry)
                        <span class="text-purple-600">Edit:</span> 
                    @endif
                    {{ $userFriendlyName ?? $emissionSource->name }}
                </h1>
                @php
                    $instructions = $industryLabel->user_friendly_description ?? $emissionSource->instructions ?? null;
                @endphp
                @if($instructions)
                    <p class="text-base text-gray-600 leading-relaxed">{{ $instructions }}</p>
                @endif
                <x-field-help key="sections.quick_input.{{ $slug }}" class="mt-3" />
            </div>
        </div>
        
        @if(isset($industryLabel) && $industryLabel && $industryLabel->common_equipment)
            <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg shadow-sm">
                <p class="text-sm font-semibold text-blue-900 mb-1">Common Equipment:</p>
                <p class="text-sm text-blue-800">{{ $industryLabel->common_equipment }}</p>
            </div>
        @endif
        
        @if(isset($industryLabel) && $industryLabel && $industryLabel->typical_units)
            <div class="mt-3 inline-flex items-center px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-700">
                <span class="font-semibold mr-2">Typical Units:</span>
                <span>{{ $industryLabel->typical_units }}</span>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-lg shadow-sm" role="alert">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold mb-2">Please correct the following errors:</h3>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Year and Location Selection Form - Professional Design -->
    <form method="GET" action="{{ route('quick-input.show', ['scope' => $scope, 'slug' => $slug]) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="fiscal_year" class="block text-sm font-semibold text-gray-700 mb-2">Year <span class="text-red-500">*</span></label>
                <select name="fiscal_year" id="fiscal_year" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                    <option value="">Select Year</option>
                    @if(isset($yearsWithMeasurements) && count($yearsWithMeasurements) > 0)
                        @foreach($yearsWithMeasurements as $year)
                            <option value="{{ $year }}" {{ ($selectedFiscalYear ?? request('fiscal_year')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="location_id" class="block text-sm font-semibold text-gray-700 mb-2">Location <span class="text-red-500">*</span></label>
                <select name="location_id" id="location_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                    <option value="">Select Location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ ($selectedLocationId ?? request('location_id')) == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-shrink-0">
                <button type="submit" class="btn btn-primary">
                    Select
                </button>
            </div>
        </div>
    </form>

    @if($selectedLocationId && $selectedFiscalYear && $measurement)
    @php
        // Hoisted out of quick-input/partials/entry-form (Phase 6 extraction).
        //
        // These four are DEFINED inside that partial, but the hidden
        // *_initial_value inputs below it read them. @include receives a COPY
        // of the parent scope, so variables set inside a partial do not flow
        // back out — without this block those inputs render empty and
        // quick-input.js loses the edit-mode initial values, silently resetting
        // fuel category / fuel type / process type / unit when editing an entry.
        // Values are identical to the partial's own copy.
        $editAdditionalData = $editEntry
            ? decode_json_field($editEntry->additional_data ?? [])
            : [];
        $editFuelCategory = $editEntry ? ($editAdditionalData['fuel_category'] ?? null) : null;
        $editFuelType = $editEntry ? ($editEntry->fuel_type ?? null) : null;
        $editUnit = $editEntry ? ($editEntry->unit ?? null) : null;
        $editProcessType = $editEntry ? ($editEntry->fuel_type ?? null) : null;
    @endphp

    {{-- Shared VERBATIM with the new theme — see quick-input/partials/entry-form.
         That file must not be re-skinned; public/js/quick-input.js is coupled to
         its class names and element ids. --}}
    @include('quick-input.partials.entry-form')
    @endif

    <!-- Existing Entries Section -->
    @if($measurement)
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <h2 class="text-xl font-bold text-gray-900">Results</h2>
                <p class="text-sm text-gray-600 mt-1">All entries for {{ $userFriendlyName ?? $emissionSource->name }} - {{ $measurement->location->name ?? 'N/A' }} ({{ $selectedFiscalYear }})</p>
            </div>
            @if($existingEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">tCO₂e</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scope</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($existingEntries as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $entry->emissionSource->name ?? 'N/A' }}</div>
                                        @php
                                            // Get type information from entry
                                            $additionalData = [];
                                            if ($entry->additional_data) {
                                                $additionalData = decode_json_field($entry->additional_data ?? []);
                                            }
                                            
                                            $energyType = $additionalData['energy_type'] ?? null;
                                            $fuelCategory = $entry->fuel_category ?? ($additionalData['fuel_category'] ?? null);
                                            $fuelType = $entry->fuel_type ?? null;
                                            
                                            // Determine what to display
                                            $typeInfo = null;
                                            if ($energyType) {
                                                // For Heat/Steam/Cooling
                                                $typeInfo = 'Type: ' . $energyType;
                                            } elseif ($fuelCategory && $fuelType) {
                                                // For Fuel sources: show category -> type
                                                $typeInfo = 'Type: ' . $fuelCategory . ' → ' . $fuelType;
                                            } elseif ($fuelType) {
                                                // Just fuel type if no category
                                                $typeInfo = 'Type: ' . $fuelType;
                                            } elseif ($fuelCategory) {
                                                // Just category if no type
                                                $typeInfo = 'Type: ' . $fuelCategory;
                                            }
                                        @endphp
                                        @if($typeInfo)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $typeInfo }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entry->measurement->location->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entry->measurement->fiscal_year ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($entry->quantity, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entry->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ co2e_t($entry->calculated_co2e, 4) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ $entry->scope }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('quick-input.view', $entry->id) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        @php
                                            $entryScopeNumber = $entry->scope ? str_replace('Scope ', '', $entry->scope) : null;
                                            $entrySlug = $entry->emissionSource->quick_input_slug ?? null;
                                            $isCurrentlyEditing = $editEntry && $editEntry->id == $entry->id;
                                        @endphp
                                        @if(!$isCurrentlyEditing && $entrySlug && $entryScopeNumber)
                                            <a href="{{ route('quick-input.show', ['scope' => $entryScopeNumber, 'slug' => $entrySlug, 'edit' => $entry->id, 'location_id' => $entry->measurement->location_id, 'fiscal_year' => $entry->measurement->fiscal_year]) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                        @elseif($isCurrentlyEditing)
                                            <span class="text-purple-600 font-medium text-xs" title="Currently editing">Editing...</span>
                                        @endif
                                        <form action="{{ route('quick-input.destroy', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-6 py-4 text-center text-gray-500">
                <p>No entries, please add some data above.</p>
            </div>
            @endif
        </div>
    @endif
    
    {{-- Hidden inputs for edit mode initial values (for JavaScript) --}}
    @if($editEntry)
        <input type="hidden" id="fuel_category_initial_value" value="{{ $editFuelCategory ?? '' }}">
        <input type="hidden" id="fuel_type_initial_value" value="{{ $editFuelType ?? '' }}">
        <input type="hidden" id="process_type_initial_value" value="{{ $editProcessType ?? '' }}">
        <input type="hidden" id="unit_of_measure_initial_value" value="{{ $editUnit ?? '' }}">
        <input type="hidden" id="unit_initial_value" value="{{ $editUnit ?? '' }}">
    @endif
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/quick-input.js')}}?v={{ time() }}"></script>
@endpush
