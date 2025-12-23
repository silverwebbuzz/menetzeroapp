@extends('admin.layouts.app')

@section('title', 'Create GWP Value | MENetZero')
@section('page-title', 'Create GWP Value')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.gwp-values.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="gas_name" class="block text-sm font-medium text-gray-700 mb-1">Gas Name *</label>
                    <input type="text" name="gas_name" id="gas_name" value="{{ old('gas_name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('gas_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gas_code" class="block text-sm font-medium text-gray-700 mb-1">Gas Code</label>
                    <input type="text" name="gas_code" id="gas_code" value="{{ old('gas_code') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="gwp_version" class="block text-sm font-medium text-gray-700 mb-1">GWP Version *</label>
                    <select name="gwp_version" id="gwp_version" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="AR4" {{ old('gwp_version') == 'AR4' ? 'selected' : '' }}>AR4</option>
                        <option value="AR5" {{ old('gwp_version') == 'AR5' ? 'selected' : '' }}>AR5</option>
                        <option value="AR6" {{ old('gwp_version', 'AR6') == 'AR6' ? 'selected' : '' }}>AR6</option>
                    </select>
                    @error('gwp_version')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gwp_100_year" class="block text-sm font-medium text-gray-700 mb-1">GWP 100-year *</label>
                    <input type="number" step="0.01" name="gwp_100_year" id="gwp_100_year" value="{{ old('gwp_100_year') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('gwp_100_year')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gwp_20_year" class="block text-sm font-medium text-gray-700 mb-1">GWP 20-year</label>
                    <input type="number" step="0.01" name="gwp_20_year" id="gwp_20_year" value="{{ old('gwp_20_year') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="gwp_500_year" class="block text-sm font-medium text-gray-700 mb-1">GWP 500-year</label>
                    <input type="number" step="0.01" name="gwp_500_year" id="gwp_500_year" value="{{ old('gwp_500_year') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_kyoto_protocol" id="is_kyoto_protocol" value="1" {{ old('is_kyoto_protocol') ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_kyoto_protocol" class="ml-2 block text-sm text-gray-700">Kyoto Protocol Gas</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.gwp-values') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Create GWP Value
                </button>
            </div>
        </form>
    </div>
@endsection

