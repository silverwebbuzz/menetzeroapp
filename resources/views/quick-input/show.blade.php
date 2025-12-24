@extends('layouts.app')

@section('title', ($userFriendlyName ?? $emissionSource->name) . ' - Quick Input - MENetZero')
@section('page-title', $userFriendlyName ?? $emissionSource->name)

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
    <form method="POST" action="{{ route('quick-input.store', ['scope' => $scope, 'slug' => $slug]) }}" class="bg-white rounded-lg shadow p-6">
        @csrf

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
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" id="quantity" step="0.0001" value="{{ old('quantity') }}" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                <select name="unit" id="unit" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Unit</option>
                    @if(!empty($availableUnits))
                        @foreach($availableUnits as $unit)
                            <option value="{{ $unit }}" {{ old('unit') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    @else
                        <option value="{{ $emissionSource->default_unit ?? 'unit' }}">{{ $emissionSource->default_unit ?? 'unit' }}</option>
                    @endif
                </select>
                @error('unit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                                <p class="mt-1 text-xs text-gray-500">{{ $field->description }}</p>
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

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('quick-input.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                Add to Footprint
            </button>
        </div>
    </form>
</div>
@endsection

