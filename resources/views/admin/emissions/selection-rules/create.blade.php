@extends('admin.layouts.app')

@section('title', 'Create Selection Rule | MENetZero')
@section('page-title', 'Create Selection Rule')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.selection-rules.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="emission_source_id" class="block text-sm font-medium text-gray-700 mb-1">Emission Source *</label>
                    <select name="emission_source_id" id="emission_source_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Source</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ old('emission_source_id') == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                        @endforeach
                    </select>
                    @error('emission_source_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="rule_name" class="block text-sm font-medium text-gray-700 mb-1">Rule Name *</label>
                    <input type="text" name="rule_name" id="rule_name" value="{{ old('rule_name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('rule_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <input type="number" name="priority" id="priority" value="{{ old('priority', 0) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-xs text-gray-500">Higher priority = selected first</p>
                </div>

                <div>
                    <label for="emission_factor_id" class="block text-sm font-medium text-gray-700 mb-1">Emission Factor</label>
                    <select name="emission_factor_id" id="emission_factor_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">None</option>
                        @foreach($factors as $factor)
                            <option value="{{ $factor->id }}" {{ old('emission_factor_id') == $factor->id ? 'selected' : '' }}>
                                {{ $factor->emissionSource->name ?? 'N/A' }} - {{ number_format($factor->factor_value, 6) }} {{ $factor->unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="conditions" class="block text-sm font-medium text-gray-700 mb-2">Conditions (JSON)</label>
                <textarea name="conditions" id="conditions" rows="6" placeholder='{"region": "UAE", "fuel_type": "Natural Gas", "unit": "kWh"}'
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ old('conditions') ? json_encode(old('conditions'), JSON_PRETTY_PRINT) : '{}' }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Enter as JSON object, e.g., {"region": "UAE", "fuel_type": "Natural Gas"}</p>
                @error('conditions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.selection-rules') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Create Rule
                </button>
            </div>
        </form>
    </div>
@endsection

