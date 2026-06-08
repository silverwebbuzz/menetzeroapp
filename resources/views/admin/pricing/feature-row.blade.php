@extends('admin.layouts.app')

@php $isEdit = $row->exists; @endphp

@section('title', ($isEdit ? 'Edit' : 'New') . ' Feature Row | MENetZero')
@section('page-title', ($isEdit ? 'Edit' : 'New') . ' Feature Row')

@section('content')
    <div class="bg-white shadow rounded-lg p-6 max-w-3xl">
        <form method="POST" action="{{ $isEdit ? route('admin.pricing.feature-rows.update', $row->id) : route('admin.pricing.feature-rows.store') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="mb-5">
                <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Feature label *</label>
                <input type="text" name="label" id="label" value="{{ old('label', $row->label) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                <div>
                    <label for="value_starter" class="block text-sm font-medium text-gray-700 mb-1">Starter</label>
                    <input type="text" name="value_starter" id="value_starter" value="{{ old('value_starter', $row->value_starter) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label for="value_growth" class="block text-sm font-medium text-gray-700 mb-1">Growth</label>
                    <input type="text" name="value_growth" id="value_growth" value="{{ old('value_growth', $row->value_growth) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label for="value_enterprise" class="block text-sm font-medium text-gray-700 mb-1">Enterprise</label>
                    <input type="text" name="value_enterprise" id="value_enterprise" value="{{ old('value_enterprise', $row->value_enterprise) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-5">Type <code>yes</code> for a tick (✓), leave blank or <code>no</code> for a dash (—), or any text such as "Up to 10", "2 Years", "Limited".</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $row->sort_order ?? 0) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div class="flex items-center mt-6">
                    <input type="checkbox" name="coming_soon" id="coming_soon" value="1" {{ old('coming_soon', $row->coming_soon) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="coming_soon" class="ml-2 block text-sm text-gray-700">Mark "Coming soon"</label>
                </div>
                <div class="flex items-center mt-6">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $isEdit ? $row->is_active : true) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active (show on pricing page)</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.pricing.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">{{ $isEdit ? 'Update' : 'Create' }} Row</button>
            </div>
        </form>
    </div>
@endsection
