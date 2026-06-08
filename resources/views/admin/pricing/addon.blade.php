@extends('admin.layouts.app')

@php
    $isEdit = $addon->exists;
    $itemsText = collect($addon->items ?? [])->map(function ($item) {
        return ($item['label'] ?? '') . (!empty($item['soon']) ? ' | soon' : '');
    })->implode("\n");
@endphp

@section('title', ($isEdit ? 'Edit' : 'New') . ' Scope 3 Add-On | MENetZero')
@section('page-title', ($isEdit ? 'Edit' : 'New') . ' Scope 3 Add-On')

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-3xl">
        <form method="POST" action="{{ $isEdit ? route('admin.pricing.addons.update', $addon->id) : route('admin.pricing.addons.store') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Add-on name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $addon->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="price_display" class="block text-sm font-medium text-gray-700 mb-1">Price (display text)</label>
                    <input type="text" name="price_display" id="price_display" value="{{ old('price_display', $addon->price_display) }}"
                           placeholder="e.g. AED 10,000 – 15,000 / year"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>

            <div class="mb-5">
                <label for="items" class="block text-sm font-medium text-gray-700 mb-1">Included items</label>
                <textarea name="items" id="items" rows="8"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ old('items', $itemsText) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">One item per line. Append <code>| soon</code> to tag an item as "Coming soon". Example:<br>
                    <code>Business Travel</code><br><code>Supplier Mapping | soon</code></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $addon->sort_order ?? 0) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div class="flex items-center mt-6">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $isEdit ? $addon->is_active : true) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active (show on pricing page)</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.pricing.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">{{ $isEdit ? 'Update' : 'Create' }} Add-On</button>
            </div>
        </form>
    </div>
@endsection
