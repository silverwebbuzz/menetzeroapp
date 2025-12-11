@extends('layouts.app')

@section('title', 'Roles List - MenetZero')
@section('page-title', 'Roles List')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Roles List</h1>
        <p class="mt-2 text-gray-600">A role provided access to predefined menus and features so that depending on assigned role an administrator can have access to what user needs.</p>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($customRoles as $role)
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
            <!-- User Count -->
            <div class="mb-4">
                <span class="text-xs font-medium text-gray-500">Total {{ $role->users_count ?? 0 }} users</span>
            </div>

            <!-- Role Name -->
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $role->role_name }}</h3>

            <!-- Edit Link -->
            <a href="{{ route('roles.edit', $role) }}" class="text-sm text-blue-600 hover:text-blue-800 mb-4 inline-block">Edit Role</a>

            <!-- User Avatars -->
            <div class="flex items-center justify-between mt-4">
                <div class="flex -space-x-2">
                    @php
                        $users = $role->users()->with('user')->where('is_active', true)->limit(4)->get();
                        $totalUsers = $role->users_count ?? $role->users()->where('is_active', true)->count();
                    @endphp
                    @foreach($users as $userRole)
                        @if($userRole->user)
                            <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-700">
                                {{ strtoupper(substr($userRole->user->name, 0, 1)) }}
                            </div>
                        @endif
                    @endforeach
                    @if($totalUsers > 4)
                        <div class="w-8 h-8 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                            +{{ $totalUsers - 4 }}
                        </div>
                    @endif
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12">
            <p class="text-gray-500">No roles found. Create your first role to get started.</p>
        </div>
        @endforelse

        <!-- Add New Role Card -->
        <div class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-6 flex flex-col items-center justify-center hover:border-orange-500 transition-colors">
            <a href="{{ route('roles.create') }}" class="w-full text-center">
                <button class="w-full mb-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Add New Role
                </button>
            </a>
            <p class="text-sm text-gray-600 text-center">Add new role, if it doesn't exist.</p>
            <div class="mt-4 w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
        </div>
    </div>
</div>
@endsection
