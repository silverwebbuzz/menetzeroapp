@extends('admin.layouts.app')

@section('title', 'Create Role Template | MENetZero')
@section('page-title', 'Create Role Template')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.role-templates.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="template_code" class="block text-sm font-medium text-gray-700 mb-1">Template Code *</label>
                    <input type="text" name="template_code" id="template_code" value="{{ old('template_code') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('template_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="template_name" class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                    <input type="text" name="template_name" id="template_name" value="{{ old('template_name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                    @error('template_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_system_template" id="is_system_template" value="1" {{ old('is_system_template') ? 'checked' : '' }}
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="is_system_template" class="ml-2 block text-sm text-gray-700">System Template</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                <div class="border border-gray-300 rounded-lg p-4 max-h-96 overflow-y-auto">
                    @forelse($permissions as $module => $modulePermissions)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2 capitalize">{{ $module }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($modulePermissions as $permission)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No permissions available.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.role-templates') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Create Template
                </button>
            </div>
        </form>
    </div>
@endsection
