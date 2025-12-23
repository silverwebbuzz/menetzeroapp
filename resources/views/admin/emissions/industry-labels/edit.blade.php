@extends('admin.layouts.app')

@section('title', 'Edit Industry Label | MENetZero')
@section('page-title', 'Edit Industry Label')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.industry-labels.update', $label->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="emission_source_id" class="block text-sm font-medium text-gray-700 mb-1">Emission Source *</label>
                    <select name="emission_source_id" id="emission_source_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Source</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ old('emission_source_id', $label->emission_source_id) == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                        @endforeach
                    </select>
                    @error('emission_source_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="industry_category_id" class="block text-sm font-medium text-gray-700 mb-1">Industry Category</label>
                    <select name="industry_category_id" id="industry_category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">None</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('industry_category_id', $label->industry_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user_friendly_name" class="block text-sm font-medium text-gray-700 mb-1">User-Friendly Name *</label>
                    <input type="text" name="user_friendly_name" id="user_friendly_name" value="{{ old('user_friendly_name', $label->user_friendly_name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('user_friendly_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="match_level" class="block text-sm font-medium text-gray-700 mb-1">Match Level</label>
                    <select name="match_level" id="match_level"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="">None</option>
                        <option value="1" {{ old('match_level', $label->match_level) == '1' ? 'selected' : '' }}>Level 1 (Sector)</option>
                        <option value="2" {{ old('match_level', $label->match_level) == '2' ? 'selected' : '' }}>Level 2 (Industry)</option>
                        <option value="3" {{ old('match_level', $label->match_level) == '3' ? 'selected' : '' }}>Level 3 (Subcategory)</option>
                    </select>
                </div>

                <div>
                    <label for="unit_type" class="block text-sm font-medium text-gray-700 mb-1">Unit Type</label>
                    <input type="text" name="unit_type" id="unit_type" value="{{ old('unit_type', $label->unit_type) }}" placeholder="e.g., Main Factory, Office Building"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $label->display_order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="also_match_children" id="also_match_children" value="1" {{ old('also_match_children', $label->also_match_children) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="also_match_children" class="ml-2 block text-sm text-gray-700">Also Match Children</label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $label->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="user_friendly_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="user_friendly_description" id="user_friendly_description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('user_friendly_description', $label->user_friendly_description) }}</textarea>
            </div>

            <div class="mt-6">
                <label for="typical_units" class="block text-sm font-medium text-gray-700 mb-1">Typical Units</label>
                <input type="text" name="typical_units" id="typical_units" value="{{ old('typical_units', $label->typical_units) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.industry-labels') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Update Label
                </button>
            </div>
        </form>
    </div>
@endsection

