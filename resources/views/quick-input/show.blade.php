@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/quick-input.css') }}">
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

    <!-- Form -->
    <form method="POST" action="{{ route('quick-input.store', ['scope' => $scope, 'slug' => $slug]) }}" 
          class="bg-white rounded-lg shadow p-6"
          data-source-id="{{ $emissionSource->id }}">
        @csrf
        <input type="hidden" name="emission_source_id" value="{{ $emissionSource->id }}">

        <!-- Year and Location Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                <input type="number" name="fiscal_year" id="fiscal_year" value="{{ old('fiscal_year', date('Y')) }}" min="2000" max="2100" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                @error('fiscal_year')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
                <select name="location_id" id="location_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('location_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Quantity and Unit -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">
                    Quantity *
                    @if($emissionSource->default_unit)
                        <span class="text-xs text-gray-500 font-normal">(in {{ $emissionSource->default_unit }})</span>
                    @endif
                </label>
                <input type="number" name="quantity" id="quantity" step="0.0001" value="{{ old('quantity') }}" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                       placeholder="Enter quantity">
                @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Enter the amount consumed or used</p>
            </div>
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
                <p class="mt-1 text-xs text-gray-500">Select the unit of measurement</p>
            </div>
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

        <!-- Dynamic Form Fields -->
        @if($formFields->count() > 0)
            <div class="mb-6 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($formFields as $field)
                        <div>
                            <label for="{{ $field->field_name }}" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $field->label }}
                                @if($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            @if($field->field_type === 'select')
                                <select name="{{ $field->field_name }}" id="{{ $field->field_name }}"
                                        {{ $field->is_required ? 'required' : '' }}
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select {{ $field->label }}</option>
                                    @if($field->options)
                                        @foreach(json_decode($field->options, true) as $option)
                                            <option value="{{ $option }}" {{ old($field->field_name) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            @elseif($field->field_type === 'textarea')
                                <textarea name="{{ $field->field_name }}" id="{{ $field->field_name }}" rows="3"
                                          {{ $field->is_required ? 'required' : '' }}
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">{{ old($field->field_name) }}</textarea>
                            @else
                                <input type="{{ $field->field_type }}" name="{{ $field->field_name }}" id="{{ $field->field_name }}"
                                       value="{{ old($field->field_name) }}"
                                       {{ $field->is_required ? 'required' : '' }}
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            @endif
                            @if($field->description)
                                <span class="tooltip">
                                    <svg class="w-4 h-4 inline text-gray-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="tooltiptext">{{ $field->description }}</span>
                                </span>
                            @endif
                            @error($field->field_name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Notes -->
        <div class="mb-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">{{ old('notes') }}</textarea>
        </div>

        <!-- Calculation Preview Section -->
        <div id="calculation-preview" class="mb-6 hidden">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Calculation Preview</h3>
                <div id="preview-content" class="text-sm text-blue-800">
                    <!-- Preview content will be inserted here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('quick-input.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="button" id="calculate-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Calculate
            </button>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                Add to Footprint
            </button>
        </div>
    </form>

    <!-- Existing Entries Section -->
    @if($measurement && $existingEntries->count() > 0)
        <div class="mt-8 bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Existing Entries for {{ $userFriendlyName ?? $emissionSource->name }}</h2>
                <p class="text-sm text-gray-600 mt-1">View and manage your previous entries for this emission source.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CO2e (kg)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($existingEntries as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : ($entry->created_at ? $entry->created_at->format('Y-m-d') : 'N/A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entry->quantity ? number_format($entry->quantity, 2) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entry->unit ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $entry->calculated_co2e ? number_format($entry->calculated_co2e, 2) : '0.00' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ Str::limit($entry->notes ?? '', 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('quick-input.view', $entry->id) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
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
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <a href="{{ route('quick-input.index', ['source_id' => $emissionSource->id]) }}" class="text-sm text-purple-600 hover:text-purple-800">
                    View all entries for {{ $userFriendlyName ?? $emissionSource->name }} →
                </a>
            </div>
        </div>
    @elseif($measurement && $existingEntries->count() == 0)
        <div class="mt-8 bg-gray-50 rounded-lg p-6 text-center">
            <p class="text-gray-600">No entries yet for {{ $userFriendlyName ?? $emissionSource->name }}. Add your first entry above.</p>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/quick-input.js') }}"></script>
@endpush

