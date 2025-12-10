@extends('layouts.app')

@section('title', 'Edit Role - MenetZero')
@section('page-title', 'Edit Role')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Role: {{ $role->role_name }}</h1>

        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Role Name -->
            <div class="mb-6">
                <label for="role_name" class="block text-sm font-medium text-gray-700 mb-2">Role Name *</label>
                <input type="text" name="role_name" id="role_name" required
                       value="{{ old('role_name', $role->role_name) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('role_name') border-red-500 @enderror">
                @error('role_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('description', $role->description) }}</textarea>
            </div>

            <!-- Permissions -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Permissions *</label>
                <div class="border border-gray-300 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-2">
                        @php
                            $currentPermissions = is_array($role->permissions) ? $role->permissions : [];
                        @endphp
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="view_dashboard" 
                                   {{ in_array('view_dashboard', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">View Dashboard</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_locations"
                                   {{ in_array('manage_locations', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Locations</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_measurements"
                                   {{ in_array('manage_measurements', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Measurements</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="upload_documents"
                                   {{ in_array('upload_documents', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Upload Documents</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="view_reports"
                                   {{ in_array('view_reports', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">View Reports</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_staff"
                                   {{ in_array('manage_staff', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Staff</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="manage_settings"
                                   {{ in_array('manage_settings', $currentPermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Manage Settings</span>
                        </label>
                    </div>
                </div>
                @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" 
                           {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('roles.index') }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Update Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

