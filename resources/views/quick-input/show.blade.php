@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

@push('styles')
<link rel="stylesheet" href="/public/css/quick-input.css">
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ $userFriendlyName ?? $emissionSource->name }}</h1>
        @if($emissionSource->instructions)
            <p class="mt-2 text-gray-600">{{ $emissionSource->instructions }}</p>
        @endif
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Year and Location Selection Form -->
    <form method="GET" action="{{ route('quick-input.show', ['scope' => $scope, 'slug' => $slug]) }}" class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                <select name="fiscal_year" id="fiscal_year" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Year</option>
                    @if(isset($yearsWithMeasurements) && count($yearsWithMeasurements) > 0)
                        @foreach($yearsWithMeasurements as $year)
                            <option value="{{ $year }}" {{ ($selectedFiscalYear ?? request('fiscal_year')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
                <select name="location_id" id="location_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ ($selectedLocationId ?? request('location_id')) == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 text-right">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                Select
            </button>
        </div>
    </form>

    @if($selectedLocationId && $selectedFiscalYear && $measurement)
    <!-- Entry Form -->
    <form method="POST" action="{{ route('quick-input.store', ['scope' => $scope, 'slug' => $slug]) }}" 
          class="bg-white rounded-lg shadow p-6 mb-6"
          data-source-id="{{ $emissionSource->id }}">
        @csrf
        <input type="hidden" name="emission_source_id" value="{{ $emissionSource->id }}">
        <input type="hidden" name="location_id" value="{{ $selectedLocationId }}">
        <input type="hidden" name="fiscal_year" value="{{ $selectedFiscalYear }}">

        <!-- Main Input Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Unit of Measure -->
            @php
                $unitField = $formFields->firstWhere('field_name', 'unit_of_measure');
            @endphp
            @if($unitField)
                <div>
                    <label for="unit_of_measure" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $unitField->field_label ?? 'Unit of Measure' }} *
                    </label>
                    <select name="unit_of_measure" id="unit_of_measure" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select an option</option>
                        @if($unitField->field_options)
                            @php
                                $options = is_array($unitField->field_options) ? $unitField->field_options : json_decode($unitField->field_options, true);
                            @endphp
                            @if(is_array($options))
                                @foreach($options as $option)
                                    @if(is_array($option))
                                        <option value="{{ $option['value'] ?? $option }}" {{ old('unit_of_measure') == ($option['value'] ?? $option) ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? $option }}</option>
                                    @else
                                        <option value="{{ $option }}" {{ old('unit_of_measure') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                    </select>
                    @error('unit_of_measure')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <!-- Fallback Unit Select -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                    <select name="unit" id="unit" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Unit</option>
                        @if(!empty($availableUnits))
                            @foreach($availableUnits as $unit)
                                <option value="{{ $unit }}" {{ old('unit', $emissionSource->default_unit) == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                            @endforeach
                        @else
                            <option value="{{ $emissionSource->default_unit ?? 'unit' }}" selected>{{ $emissionSource->default_unit ?? 'unit' }}</option>
                        @endif
                    </select>
                    @error('unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Amount/Quantity -->
            @php
                $amountField = $formFields->firstWhere('field_name', 'amount');
            @endphp
            @if($amountField)
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $amountField->field_label ?? 'Amount' }} *
                    </label>
                    <input type="number" name="amount" id="amount" step="0.01" value="{{ old('amount') }}" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Enter amount">
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if($amountField->help_text)
                        <p class="mt-1 text-xs text-gray-500">{{ $amountField->help_text }}</p>
                    @endif
                </div>
            @else
                <!-- Fallback Quantity Input -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" id="quantity" step="0.0001" value="{{ old('quantity') }}" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Enter quantity">
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Enter the amount consumed or used</p>
                </div>
            @endif
        </div>

        <!-- Entry Date -->
        <div class="mb-6">
            <label for="entry_date" class="block text-sm font-medium text-gray-700 mb-1">Entry Date *</label>
            <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            @error('entry_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Dynamic Form Fields (Optional Additional Information) -->
        @php
            // Filter and deduplicate fields - only show unique field names
            $seenFieldNames = [];
            $additionalFields = $formFields->filter(function($field) use (&$seenFieldNames) {
                // Skip unit_of_measure, amount, and comments (we'll handle comments separately)
                if (in_array($field->field_name, ['unit_of_measure', 'amount', 'comments']) || $field->is_required) {
                    return false;
                }
                // Deduplicate by field_name - only show first occurrence
                if (in_array($field->field_name, $seenFieldNames)) {
                    return false;
                }
                $seenFieldNames[] = $field->field_name;
                return true;
            });
            $commentsField = $formFields->firstWhere('field_name', 'comments');
        @endphp
        @if($additionalFields->count() > 0 || $commentsField)
            <div class="mb-6 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Data</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($additionalFields as $field)
                        <div>
                            <label for="{{ $field->field_name }}" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                            </label>
                            @if($field->field_type === 'select')
                                <select name="{{ $field->field_name }}" id="{{ $field->field_name }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select {{ $field->field_label ?? $field->field_name }}</option>
                                    @if($field->field_options)
                                        @php
                                            $options = is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true);
                                        @endphp
                                        @if(is_array($options))
                                            @foreach($options as $option)
                                                @if(is_array($option))
                                                    <option value="{{ $option['value'] ?? $option }}" {{ old($field->field_name) == ($option['value'] ?? $option) ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? $option }}</option>
                                                @else
                                                    <option value="{{ $option }}" {{ old($field->field_name) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endif
                                </select>
                            @elseif($field->field_type === 'textarea')
                                <textarea name="{{ $field->field_name }}" id="{{ $field->field_name }}" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">{{ old($field->field_name) }}</textarea>
                            @else
                                <input type="{{ $field->field_type }}" name="{{ $field->field_name }}" id="{{ $field->field_name }}"
                                       value="{{ old($field->field_name) }}"
                                       placeholder="{{ $field->field_placeholder ?? '' }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            @endif
                            @if($field->help_text)
                                <p class="mt-1 text-xs text-gray-500">{{ $field->help_text }}</p>
                            @endif
                            @error($field->field_name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                    
                    @if($commentsField)
                        <!-- Comments field (full width) -->
                        <div class="md:col-span-2">
                            <label for="comments" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $commentsField->field_label ?? 'Comments' }}
                            </label>
                            <textarea name="comments" id="comments" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                      placeholder="{{ $commentsField->field_placeholder ?? 'Add any additional notes...' }}">{{ old('comments') }}</textarea>
                            @if($commentsField->help_text)
                                <p class="mt-1 text-xs text-gray-500">{{ $commentsField->help_text }}</p>
                            @endif
                            @error('comments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Notes field (for backward compatibility, only if comments field doesn't exist) -->
        @if(!$commentsField)
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">{{ old('notes') }}</textarea>
            </div>
        @endif

        <!-- Calculation Preview Section -->
        <div id="calculation-preview" class="mb-6 hidden">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-green-900 mb-2">Calculation Preview</h3>
                <div id="preview-content" class="text-sm text-green-800">
                    <!-- Preview content will be inserted here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4">
            <button type="button" id="calculate-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Calculate
            </button>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Calculate & Add to Footprint
            </button>
        </div>
    </form>
    @else
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 3.493a.75.75 0 00-1.47 0L1.485 16.493a.75.75 0 00.659 1.007h15.712a.75.75 0 00.659-1.007L8.485 3.493zM12 10a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm-1.5 3a.75.75 0 100 1.5.75.75 0 000-1.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-800">
                    Please select a <strong>Fiscal Year</strong> and <strong>Location</strong> above to start entering data.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Existing Entries Section -->
    @if($measurement)
        <div class="mt-8 bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Results</h2>
                <p class="text-sm text-gray-600 mt-1">All entries for {{ $userFriendlyName ?? $emissionSource->name }} - {{ $measurement->location->name ?? 'N/A' }} ({{ $selectedFiscalYear }})</p>
            </div>
            @if($existingEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emissions (tCO₂e)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($existingEntries as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : ($entry->created_at ? $entry->created_at->format('Y-m-d') : 'N/A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $entry->calculated_co2e ? number_format($entry->calculated_co2e / 1000, 2) : '0.00' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ number_format($entry->quantity ?? 0, 2) }} {{ $entry->unit ?? '' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ Str::limit($entry->notes ?? '', 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('quick-input.edit', $entry->id) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
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
</div>

@endsection

@push('scripts')
<script src="/public/js/quick-input.js"></script>
@endpush
