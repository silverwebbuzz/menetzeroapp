@extends('admin.layouts.app')

@section('title', 'Edit Unit Conversion | MENetZero')
@section('page-title', 'Edit Unit Conversion')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.unit-conversions.update', $conversion->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="from_unit" class="block text-sm font-medium text-gray-700 mb-1">From Unit *</label>
                    <input type="text" name="from_unit" id="from_unit" value="{{ old('from_unit', $conversion->from_unit) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('from_unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="to_unit" class="block text-sm font-medium text-gray-700 mb-1">To Unit *</label>
                    <input type="text" name="to_unit" id="to_unit" value="{{ old('to_unit', $conversion->to_unit) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('to_unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="conversion_factor" class="block text-sm font-medium text-gray-700 mb-1">Conversion Factor *</label>
                    <input type="number" step="0.000001" name="conversion_factor" id="conversion_factor" value="{{ old('conversion_factor', $conversion->conversion_factor) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Multiply from_unit by this value to get to_unit</p>
                    @error('conversion_factor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                    <input type="text" name="fuel_type" id="fuel_type" value="{{ old('fuel_type', $conversion->fuel_type) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <input type="text" name="region" id="region" value="{{ old('region', $conversion->region) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $conversion->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('notes', $conversion->notes) }}</textarea>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.unit-conversions') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Update Conversion
                </button>
            </div>
        </form>
    </div>
@endsection

