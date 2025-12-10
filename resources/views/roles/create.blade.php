@extends('layouts.app')

@section('title', 'Create Role - MenetZero')
@section('page-title', 'Create Role')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Custom Role</h1>

        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <!-- Role Name -->
            <div class="mb-6">
                <label for="role_name" class="block text-sm font-medium text-gray-700 mb-2">Role Name *</label>
                <input type="text" name="role_name" id="role_name" required
                       value="{{ old('role_name') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('role_name') border-red-500 @enderror">
                @error('role_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('description') }}</textarea>
            </div>

            <!-- Permissions -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Permissions *</label>
                <div class="border border-gray-300 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="view_dashboard" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">View Dashboard</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_locations" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Locations</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_measurements" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Measurements</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="upload_documents" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Upload Documents</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="view_reports" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">View Reports</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_staff" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Staff</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_settings" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Settings</span>
                        </label>
                    </div>
                </div>
                @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Based on Template -->
            @if($templates->count() > 0)
            <div class="mb-6">
                <label for="based_on_template" class="block text-sm font-medium text-gray-700 mb-2">Based on Template (Optional)</label>
                <select name="based_on_template" id="based_on_template"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">None</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->template_code }}" {{ old('based_on_template') == $template->template_code ? 'selected' : '' }}>
                            {{ $template->template_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('roles.index') }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

