@extends('layouts.app')

@section('title', 'Invite Staff Member - MenetZero')
@section('page-title', 'Invite Staff Member')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Invite Staff Member</h1>

        <form action="{{ route('staff.store') }}" method="POST">
            @csrf

            <!-- Email -->
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input type="email" name="email" id="email" required
                       value="{{ old('email') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div class="mb-6">
                <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">System Role (Optional)</label>
                <select name="role_id" id="role_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Select a system role</option>
                    <!-- Add system roles here if using Spatie -->
                </select>
            </div>

            <!-- Custom Role -->
            @if($customRoles->count() > 0)
            <div class="mb-6">
                <label for="custom_role_id" class="block text-sm font-medium text-gray-700 mb-2">Custom Role (Optional)</label>
                <select name="custom_role_id" id="custom_role_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Select a custom role</option>
                    @foreach($customRoles as $role)
                        <option value="{{ $role->id }}" {{ old('custom_role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Access Level -->
            <div class="mb-6">
                <label for="access_level" class="block text-sm font-medium text-gray-700 mb-2">Access Level *</label>
                <select name="access_level" id="access_level" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="view" {{ old('access_level') == 'view' ? 'selected' : '' }}>View Only</option>
                    <option value="edit" {{ old('access_level') == 'edit' ? 'selected' : '' }}>Edit</option>
                    <option value="full" {{ old('access_level') == 'full' ? 'selected' : '' }}>Full Access</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">
                    <strong>View:</strong> Can view data only<br>
                    <strong>Edit:</strong> Can view and edit data<br>
                    <strong>Full:</strong> Can view, edit, and manage settings
                </p>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('notes') }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('staff.index') }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Send Invitation
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

