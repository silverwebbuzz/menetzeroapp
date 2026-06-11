@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

@push('styles')
<link rel="stylesheet" href="{{asset('css/quick-input.css?v=20260608')}}">
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
    @if(($scope3LimitReached ?? false) && !$editEntry)
    <!-- Scope 3 free-plan limit reached: prompt to upgrade instead of showing the add form -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl shadow-sm p-6 mb-8">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Category limit reached</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Your plan allows <strong>{{ $scope3Limit }}</strong> entry per Scope 3 category.
                    You've reached the limit for <strong>{{ $userFriendlyName ?? $emissionSource->name }}</strong>.
                    @if($gate->isAgencyWorkspace())
                        {{ $gate->agencyLockedMessage('Full Scope 3 reporting') }}
                    @else
                        Upgrade to <strong>Enterprise</strong> for full Scope 3 reporting.
                    @endif
                </p>
                <a href="{{ $gate->upgradeRoute() }}"
                   class="inline-flex items-center px-5 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors shadow-sm">
                    {{ $gate->upgradeButtonLabel('View upgrade plans') }}
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @else
    <!-- Entry Form (Handles both Add and Edit) - Professional Design -->
    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $editEntry ? route('quick-input.update', $editEntry->id) : route('quick-input.store', ['scope' => $scope, 'slug' => $slug]) }}"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8"
          data-source-id="{{ $emissionSource->id }}">
        @csrf
        @if($editEntry)
            @method('PUT')
        @endif
        <input type="hidden" name="emission_source_id" value="{{ $emissionSource->id }}">
        <input type="hidden" name="location_id" value="{{ $selectedLocationId }}">
        <input type="hidden" name="fiscal_year" value="{{ $selectedFiscalYear }}">
        @if($editEntry)
            <input type="hidden" name="edit_entry_id" value="{{ $editEntry->id }}">
        @endif

        <!-- Split Layout: Main Form (Left 60%) and Additional Data (Right 40%) -->
        <div class="grid grid-cols-1 lg:grid-cols-[60%_40%] gap-6 mb-8">
            <!-- Left Side: Main Form Fields -->
            <div class="flex flex-col">
                @php
                    $scope2FieldNames = ['scope2_method', 'supplier_emission_factor', 'renewable_percent', 'is_biogenic'];

                    $editAdditionalData = [];
                    if ($editEntry && $editEntry->additional_data) {
                        $editAdditionalData = is_string($editEntry->additional_data) ? json_decode($editEntry->additional_data, true) : ($editEntry->additional_data ?? []);
                    }

                    $editFuelCategory = $editEntry ? ($editAdditionalData['fuel_category'] ?? null) : null;
                    $editFuelType = $editEntry ? ($editEntry->fuel_type ?? null) : null;
                    $editUnit = $editEntry ? ($editEntry->unit ?? null) : null;
                    $editProcessType = $editEntry ? ($editEntry->fuel_type ?? null) : null;

                    $seenMainFieldNames = [];
                    $mainFields = $formFields->filter(function($field) use (&$seenMainFieldNames, $scope2FieldNames) {
                        if (in_array($field->field_name, $scope2FieldNames)) {
                            return false;
                        }

                        $isMainField = $field->is_required || in_array($field->field_name, ['fuel_category', 'fuel_type', 'unit_of_measure', 'unit', 'amount', 'quantity', 'distance', 'process_type']);

                        if (!$isMainField || in_array($field->field_name, $seenMainFieldNames)) {
                            return false;
                        }

                        $seenMainFieldNames[] = $field->field_name;
                        return true;
                    })->sortBy('field_order');

                    $scope2Fields = $formFields->filter(fn($field) => in_array($field->field_name, $scope2FieldNames))->sortBy('field_order');

                    $resolveScope2FieldValue = function($field) use ($editEntry, $editAdditionalData) {
                        $name = $field->field_name;
                        if (old($name) !== null && old($name) !== '') {
                            return old($name);
                        }
                        if (!$editEntry) {
                            return $name === 'is_biogenic' ? false : '';
                        }
                        return match ($name) {
                            'scope2_method' => $editEntry->scope2_method ?? 'location',
                            'supplier_emission_factor' => $editEntry->supplier_emission_factor ?? '',
                            'renewable_percent' => $editAdditionalData['renewable_percent'] ?? '',
                            'is_biogenic' => (bool) $editEntry->is_biogenic,
                            default => $editAdditionalData[$name] ?? '',
                        };
                    };
                @endphp

                <div class="main-information-panel flex-1">
                    <div class="main-information-panel__header">
                        <h3 class="main-information-panel__title">Main Information</h3>
                        <p class="main-information-panel__subtitle">Enter the primary emission data for this source</p>
                    </div>

                    <div class="main-information-panel__fields main-information-panel__fields--grid">
                        @foreach($mainFields as $field)
                            @php
                                $isFullWidth = in_array($field->field_type, ['textarea']) || in_array($field->field_name, ['fuel_category', 'fuel_type', 'process_type']);
                            @endphp
                            @if($field->field_type === 'select')
                                <div class="form-group-stacked {{ $isFullWidth ? 'form-group-stacked--full' : '' }}">
                                    <label for="{{ $field->field_name }}" class="form-label-stacked">
                                        {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                                        @if($field->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <select name="{{ $field->field_name }}"
                                            id="{{ $field->field_name }}"
                                            data-field-name="{{ $field->field_name }}"
                                            data-depends-on="{{ $field->depends_on_field ?? '' }}"
                                            {{ $field->is_required ? 'required' : '' }}
                                            class="form-input-select">
                                        <option value="">Select an option</option>
                                        @if($field->field_options)
                                            @php
                                                $options = is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true);
                                            @endphp
                                            @if(is_array($options))
                                                @foreach($options as $option)
                                                    @php
                                                        $fieldValue = old($field->field_name);
                                                        if (!$fieldValue && $editEntry) {
                                                            if ($field->field_name === 'fuel_category') {
                                                                $fieldValue = $editFuelCategory;
                                                            } elseif ($field->field_name === 'fuel_type') {
                                                                $fieldValue = $editFuelType;
                                                            } elseif ($field->field_name === 'process_type') {
                                                                $fieldValue = $editProcessType;
                                                            } elseif ($field->field_name === 'unit_of_measure' || $field->field_name === 'unit') {
                                                                $fieldValue = $editUnit;
                                                            } else {
                                                                $fieldValue = $editAdditionalData[$field->field_name] ?? null;
                                                            }
                                                        }
                                                        $optionValue = is_array($option) ? ($option['value'] ?? $option) : $option;
                                                        $isSelected = $fieldValue == $optionValue;
                                                    @endphp
                                                    @if(is_array($option))
                                                        <option value="{{ $optionValue }}" {{ $isSelected ? 'selected' : '' }}>{{ $option['label'] ?? $optionValue }}</option>
                                                    @else
                                                        <option value="{{ $optionValue }}" {{ $isSelected ? 'selected' : '' }}>{{ $option }}</option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @endif
                                    </select>
                                    @if($field->help_text)
                                        <p class="form-help-text">{{ $field->help_text }}</p>
                                    @endif
                                    @error($field->field_name)
                                        <p class="form-error-text">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif($field->field_type === 'number')
                                <div class="form-group-stacked {{ $isFullWidth ? 'form-group-stacked--full' : '' }}">
                                    <label for="{{ $field->field_name }}" class="form-label-stacked">
                                        {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                                        @if($field->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="number"
                                           name="{{ $field->field_name }}"
                                           id="{{ $field->field_name }}"
                                           value="{{ old($field->field_name, $editEntry && ($field->field_name === 'amount' || $field->field_name === 'quantity' || $field->field_name === 'distance') ? ($editEntry->quantity ?? $editAdditionalData[$field->field_name] ?? '') : ($editAdditionalData[$field->field_name] ?? '')) }}"
                                           step="any"
                                           min="0"
                                           {{ $field->is_required ? 'required' : '' }}
                                           placeholder="{{ $field->field_placeholder ?? 'Enter ' . strtolower($field->field_label ?? $field->field_name) }}"
                                           class="form-input">
                                    @if($field->help_text)
                                        <p class="form-help-text">{{ $field->help_text }}</p>
                                    @endif
                                    @error($field->field_name)
                                        <p class="form-error-text">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <div class="form-group-stacked {{ $isFullWidth ? 'form-group-stacked--full' : '' }}">
                                    <label for="{{ $field->field_name }}" class="form-label-stacked">
                                        {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                                        @if($field->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    @if($field->field_type === 'textarea')
                                        <textarea name="{{ $field->field_name }}"
                                                  id="{{ $field->field_name }}"
                                                  rows="3"
                                                  {{ $field->is_required ? 'required' : '' }}
                                                  placeholder="{{ $field->field_placeholder ?? '' }}"
                                                  class="form-input form-textarea">{{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') }}</textarea>
                                    @else
                                        <input type="{{ $field->field_type }}"
                                               name="{{ $field->field_name }}"
                                               id="{{ $field->field_name }}"
                                               value="{{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') }}"
                                               {{ $field->is_required ? 'required' : '' }}
                                               placeholder="{{ $field->field_placeholder ?? '' }}"
                                               class="form-input">
                                    @endif
                                    @if($field->help_text)
                                        <p class="form-help-text">{{ $field->help_text }}</p>
                                    @endif
                                    @error($field->field_name)
                                        <p class="form-error-text">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if($scope2Fields->count() > 0)
                        <details class="scope2-reporting-block"@if($editEntry && ($editEntry->scope2_method === 'market' || $editEntry->supplier_emission_factor || ($editAdditionalData['renewable_percent'] ?? null))) open @endif>
                            <summary>Scope 2 reporting (optional)</summary>
                            <p class="scope2-reporting-block__intro">IFRS S2 expects location-based figures by default. Expand this section when you have supplier-specific factors or renewable energy certificates.</p>
                            <div class="scope2-reporting-block__fields">
                                @foreach($scope2Fields as $field)
                                    @php
                                        $scope2Value = $resolveScope2FieldValue($field);
                                        $isScope2FullWidth = in_array($field->field_name, ['scope2_method', 'is_biogenic']);
                                    @endphp
                                    @if($field->field_type === 'checkbox')
                                        <div class="form-group-stacked form-group-stacked--full">
                                            <div class="form-group-stacked--checkbox">
                                                <input type="hidden" name="{{ $field->field_name }}" value="0">
                                                <input type="checkbox"
                                                       name="{{ $field->field_name }}"
                                                       id="{{ $field->field_name }}"
                                                       value="1"
                                                       {{ $scope2Value ? 'checked' : '' }}>
                                                <label for="{{ $field->field_name }}" class="form-label-stacked">
                                                    {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                                                </label>
                                            </div>
                                            @if($field->help_text)
                                                <p class="form-help-text">{{ $field->help_text }}</p>
                                            @endif
                                        </div>
                                    @elseif($field->field_type === 'select')
                                        <div class="form-group-stacked {{ $isScope2FullWidth ? 'form-group-stacked--full' : '' }}">
                                            <label for="{{ $field->field_name }}" class="form-label-stacked">{{ $field->field_label }}</label>
                                            <select name="{{ $field->field_name }}" id="{{ $field->field_name }}" class="form-input-select">
                                                <option value="">Select {{ $field->field_label }}</option>
                                                @if($field->field_options)
                                                    @php
                                                        $options = is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true);
                                                    @endphp
                                                    @if(is_array($options))
                                                        @foreach($options as $option)
                                                            @php
                                                                $optionValue = is_array($option) ? ($option['value'] ?? $option) : $option;
                                                            @endphp
                                                            @if(is_array($option))
                                                                <option value="{{ $optionValue }}" {{ (string) $scope2Value === (string) $optionValue ? 'selected' : '' }}>{{ $option['label'] ?? $optionValue }}</option>
                                                            @else
                                                                <option value="{{ $optionValue }}" {{ (string) $scope2Value === (string) $optionValue ? 'selected' : '' }}>{{ $option }}</option>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                @endif
                                            </select>
                                            @if($field->help_text)
                                                <p class="form-help-text">{{ $field->help_text }}</p>
                                            @endif
                                            @error($field->field_name)
                                                <p class="form-error-text">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @else
                                        <div class="form-group-stacked {{ $isScope2FullWidth ? 'form-group-stacked--full' : '' }}">
                                            <label for="{{ $field->field_name }}" class="form-label-stacked">{{ $field->field_label }}</label>
                                            <input type="{{ $field->field_type }}"
                                                   name="{{ $field->field_name }}"
                                                   id="{{ $field->field_name }}"
                                                   value="{{ $scope2Value }}"
                                                   placeholder="{{ $field->field_placeholder ?? '' }}"
                                                   class="form-input">
                                            @if($field->help_text)
                                                <p class="form-help-text">{{ $field->help_text }}</p>
                                            @endif
                                            @error($field->field_name)
                                                <p class="form-error-text">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>

            <!-- Right Side: Additional Data -->
            <div class="flex flex-col">
                @php
                    $editAdditionalData = [];
                    if ($editEntry && $editEntry->additional_data) {
                        $editAdditionalData = is_string($editEntry->additional_data) ? json_decode($editEntry->additional_data, true) : ($editEntry->additional_data ?? []);
                    }

                    $scope2FieldNames = ['scope2_method', 'supplier_emission_factor', 'renewable_percent', 'is_biogenic'];
                    $mainFieldNames = ['fuel_category', 'fuel_type', 'unit_of_measure', 'amount', 'quantity', 'unit'];
                    $seenFieldNames = [];
                    $additionalFields = $formFields->filter(function($field) use (&$seenFieldNames, $mainFieldNames, $scope2FieldNames) {
                        if (in_array($field->field_name, $mainFieldNames) || $field->is_required || in_array($field->field_name, ['comments', 'link']) || in_array($field->field_name, $scope2FieldNames)) {
                            return false;
                        }
                        if (in_array($field->field_name, $seenFieldNames)) {
                            return false;
                        }
                        $seenFieldNames[] = $field->field_name;
                        return true;
                    });
                    $commentsField = $formFields->firstWhere('field_name', 'comments');
                    $editEvidenceLink = old('evidence_link', $editAdditionalData['evidence_link'] ?? $editAdditionalData['link'] ?? '');
                @endphp

                <div class="additional-data-panel flex flex-col flex-1">
                    <div class="additional-data-panel__header">
                        <h3 class="text-lg font-semibold text-gray-900">Additional Data</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Optional context, evidence links, uploads, and notes</p>
                    </div>

                    @if($additionalFields->count() > 0)
                        <div class="additional-data-section additional-data-panel__fields">
                            <p class="additional-data-panel__subheading">Source-specific fields</p>
                            @foreach($additionalFields as $field)
                                <div class="form-group-stacked">
                                    <label for="{{ $field->field_name }}" class="form-label-stacked">
                                        {{ $field->field_label ?? ucwords(str_replace('_', ' ', $field->field_name)) }}
                                    </label>
                                    @if($field->field_type === 'select')
                                        <select name="{{ $field->field_name }}" id="{{ $field->field_name }}" class="form-input-select">
                                            <option value="">Select {{ $field->field_label ?? $field->field_name }}</option>
                                            @if($field->field_options)
                                                @php
                                                    $options = is_array($field->field_options) ? $field->field_options : json_decode($field->field_options, true);
                                                @endphp
                                                @if(is_array($options))
                                                    @foreach($options as $option)
                                                        @if(is_array($option))
                                                            <option value="{{ $option['value'] ?? $option }}" {{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') == ($option['value'] ?? $option) ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? $option }}</option>
                                                        @else
                                                            <option value="{{ $option }}" {{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                        </select>
                                    @elseif($field->field_type === 'textarea')
                                        <textarea name="{{ $field->field_name }}" id="{{ $field->field_name }}" rows="3"
                                                  class="form-input form-textarea">{{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') }}</textarea>
                                    @else
                                        <input type="{{ $field->field_type }}" name="{{ $field->field_name }}" id="{{ $field->field_name }}"
                                               value="{{ old($field->field_name, $editAdditionalData[$field->field_name] ?? '') }}"
                                               placeholder="{{ $field->field_placeholder ?? '' }}"
                                               class="form-input">
                                    @endif
                                    @if($field->help_text)
                                        <p class="form-help-text">{{ $field->help_text }}</p>
                                    @endif
                                    @error($field->field_name)
                                        <p class="form-error-text">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="evidence-notes-panel">
                        <p class="additional-data-panel__subheading">Evidence &amp; notes</p>
                        <p class="text-xs text-gray-500 mb-4">All fields below are optional. Use a bill date, file upload, shared link (Google Sheet, DEWA portal, etc.), or comments — whatever you have.</p>

                        <div class="form-group-stacked">
                            <label for="entry_date" class="form-label-stacked">Activity / bill date</label>
                            <input type="date"
                                   name="entry_date"
                                   id="entry_date"
                                   max="{{ date('Y-m-d') }}"
                                   value="{{ old('entry_date', $editEntry?->entry_date?->format('Y-m-d') ?? '') }}"
                                   class="form-input"
                                   placeholder="Optional">
                            <p class="form-help-text">Date on the utility bill, fuel receipt, or invoice — leave blank if unknown.</p>
                            @error('entry_date')
                                <p class="form-error-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group-stacked">
                            <label for="evidence_link" class="form-label-stacked">Link</label>
                            <input type="url"
                                   name="evidence_link"
                                   id="evidence_link"
                                   value="{{ $editEvidenceLink }}"
                                   class="form-input"
                                   placeholder="e.g. SharePoint, Google Drive, or bill portal URL">
                            <p class="form-help-text">Link to supporting documents (Google Sheet, shared folder, DEWA portal, etc.) if you are not uploading a file.</p>
                            @error('evidence_link')
                                <p class="form-error-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group-stacked">
                            <label for="supporting_documents" class="form-label-stacked">Supporting documents</label>
                            <div class="evidence-file-zone">
                                <input type="file"
                                       name="supporting_documents[]"
                                       id="supporting_documents"
                                       multiple
                                       accept=".pdf,.jpg,.jpeg,.png,.webp"
                                       class="evidence-file-input">
                                <p class="evidence-file-zone__hint">PDF, JPG, PNG, or WebP — max 10 MB each, up to 5 files</p>
                            </div>
                            @error('supporting_documents')
                                <p class="form-error-text">{{ $message }}</p>
                            @enderror
                            @error('supporting_documents.*')
                                <p class="form-error-text">{{ $message }}</p>
                            @enderror
                            @if($editEntry && !empty($editEntry->supporting_docs))
                                <ul class="evidence-file-list">
                                    @foreach($editEntry->supporting_docs as $index => $doc)
                                        <li>
                                            <a href="{{ route('quick-input.documents.download', [$editEntry->id, $index]) }}"
                                               class="evidence-file-list__link">
                                                <span aria-hidden="true">📎</span>
                                                {{ $doc['filename'] ?? 'Document' }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="form-group-stacked">
                            @if($commentsField)
                                <label for="comments" class="form-label-stacked">{{ $commentsField->field_label ?? 'Comments' }}</label>
                                <textarea name="comments" id="comments" rows="3"
                                          class="form-input form-textarea"
                                          placeholder="{{ $commentsField->field_placeholder ?? 'Any extra context for this entry…' }}">{{ old('comments', $editEntry->notes ?? '') }}</textarea>
                                @if($commentsField->help_text)
                                    <p class="form-help-text">{{ $commentsField->help_text }}</p>
                                @endif
                                @error('comments')
                                    <p class="form-error-text">{{ $message }}</p>
                                @enderror
                            @else
                                <label for="notes" class="form-label-stacked">Comments</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="form-input form-textarea"
                                          placeholder="Any extra context for this entry…">{{ old('notes', $editEntry->notes ?? '') }}</textarea>
                                @error('notes')
                                    <p class="form-error-text">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation Preview Section - Below Additional Data -->
        <div id="calculation-preview" class="mb-8 hidden">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-green-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Calculation Preview
                </h3>
                <div id="preview-content" class="text-sm text-green-800 leading-relaxed">
                    <!-- Preview content will be inserted here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-2 pt-6 border-t border-gray-200">
            @if($editEntry)
                <a href="{{ route('quick-input.show', ['scope' => $scope, 'slug' => $slug, 'location_id' => $selectedLocationId, 'fiscal_year' => $selectedFiscalYear]) }}" class="btn btn-secondary">
                    Cancel
                </a>
            @endif
            <button type="button" id="calculate-btn" class="btn btn-outline">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Calculate
            </button>
            <button type="submit" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $editEntry ? 'Update Entry' : 'Calculate & Add to Footprint' }}
            </button>
        </div>
    </form>
    @endif
    @else
    <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm p-5 mb-8">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 3.493a.75.75 0 00-1.47 0L1.485 16.493a.75.75 0 00.659 1.007h15.712a.75.75 0 00.659-1.007L8.485 3.493zM12 10a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm-1.5 3a.75.75 0 100 1.5.75.75 0 000-1.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-semibold text-yellow-800 mb-1">Action Required</h3>
                <p class="text-sm text-yellow-700">
                    Please select a <strong>Fiscal Year</strong> and <strong>Location</strong> above to start entering data.
                </p>
            </div>
        </div>
    </div>
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
                                                $additionalData = is_string($entry->additional_data) ? json_decode($entry->additional_data, true) : ($entry->additional_data ?? []);
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
