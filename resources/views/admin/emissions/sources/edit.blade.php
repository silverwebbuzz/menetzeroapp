@extends('admin.layouts.app')

@section('title', 'Edit Emission Source | MENetZero')
@section('page-title', 'Edit Emission Source')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.emissions.sources.update', $source->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $source->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="scope" class="block text-sm font-medium text-gray-700 mb-1">Scope *</label>
                    <select name="scope" id="scope" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        <option value="1" {{ old('scope', $source->scope) == '1' ? 'selected' : '' }}>Scope 1</option>
                        <option value="2" {{ old('scope', $source->scope) == '2' ? 'selected' : '' }}>Scope 2</option>
                        <option value="3" {{ old('scope', $source->scope) == '3' ? 'selected' : '' }}>Scope 3</option>
                    </select>
                    @error('scope')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" name="category" id="category" value="{{ old('category', $source->category) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div>
                    <label for="quick_input_slug" class="block text-sm font-medium text-gray-700 mb-1">Quick Input Slug</label>
                    <input type="text" name="quick_input_slug" id="quick_input_slug" value="{{ old('quick_input_slug', $source->quick_input_slug) }}" placeholder="e.g., natural-gas"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('quick_input_slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quick_input_order" class="block text-sm font-medium text-gray-700 mb-1">Quick Input Order</label>
                    <input type="number" name="quick_input_order" id="quick_input_order" value="{{ old('quick_input_order', $source->quick_input_order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_quick_input" id="is_quick_input" value="1" {{ old('is_quick_input', $source->is_quick_input) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_quick_input" class="ml-2 block text-sm text-gray-700">Show in Quick Input</label>
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('description', $source->description) }}</textarea>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.emissions.sources') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Update Source
                </button>
            </div>
        </form>
    </div>
@endsection

