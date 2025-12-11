@extends('layouts.app')

@section('title', 'Edit Role - MenetZero')
@section('page-title', 'Edit Role')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Edit Role: {{ $role->role_name }}</h1>
        <p class="text-sm text-gray-600 mb-6">Set role permissions</p>

        <form action="{{ route('roles.update', $role) }}" method="POST" id="roleForm">
            @csrf
            @method('PUT')

            <!-- Role Name -->
            <div class="mb-6">
                <label for="role_name" class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                <input type="text" name="role_name" id="role_name" required
                       value="{{ old('role_name', $role->role_name) }}"
                       placeholder="Enter a role name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role_name') border-red-500 @enderror">
                @error('role_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          placeholder="Enter role description"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $role->description) }}</textarea>
            </div>

            <!-- Role Permissions -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Role Permissions
                        <svg class="w-4 h-4 inline ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Select All</span>
                    </label>
                </div>

                <div class="border border-gray-300 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3">
                        <div class="grid grid-cols-5 gap-4">
                            <div class="col-span-2 text-sm font-medium text-gray-700">Module</div>
                            <div class="text-sm font-medium text-gray-700 text-center">View</div>
                            <div class="text-sm font-medium text-gray-700 text-center">Add</div>
                            <div class="text-sm font-medium text-gray-700 text-center">Edit</div>
                            <div class="text-sm font-medium text-gray-700 text-center">Delete</div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($permissions as $module => $modulePermissions)
                        <div class="px-4 py-3 hover:bg-gray-50">
                            <div class="grid grid-cols-5 gap-4 items-center">
                                <div class="col-span-2">
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $module)) }}</span>
                                </div>
                                @php
                                    $viewPerm = $modulePermissions->firstWhere('action', 'view');
                                    $addPerm = $modulePermissions->firstWhere('action', 'add');
                                    $editPerm = $modulePermissions->firstWhere('action', 'edit');
                                    $deletePerm = $modulePermissions->firstWhere('action', 'delete');
                                @endphp
                                <div class="text-center">
                                    @if($viewPerm)
                                        <input type="checkbox" name="permission_ids[]" value="{{ $viewPerm->id }}" 
                                               {{ in_array($viewPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                               class="module-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    @endif
                                </div>
                                <div class="text-center">
                                    @if($addPerm)
                                        <input type="checkbox" name="permission_ids[]" value="{{ $addPerm->id }}" 
                                               {{ in_array($addPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                               class="module-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    @endif
                                </div>
                                <div class="text-center">
                                    @if($editPerm)
                                        <input type="checkbox" name="permission_ids[]" value="{{ $editPerm->id }}" 
                                               {{ in_array($editPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                               class="module-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    @endif
                                </div>
                                <div class="text-center">
                                    @if($deletePerm)
                                        <input type="checkbox" name="permission_ids[]" value="{{ $deletePerm->id }}" 
                                               {{ in_array($deletePerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                               class="module-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @error('permission_ids')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" 
                           {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                <a href="{{ route('roles.index') }}" class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');

    // Update Select All state on load
    const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
    const someChecked = Array.from(moduleCheckboxes).some(cb => cb.checked);
    selectAll.checked = allChecked;
    selectAll.indeterminate = someChecked && !allChecked;

    // Select All functionality
    selectAll.addEventListener('change', function() {
        moduleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        selectAll.indeterminate = false;
    });

    // Update Select All when individual checkboxes change
    moduleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(moduleCheckboxes).some(cb => cb.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        });
    });
});
</script>
@endsection
