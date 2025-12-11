@extends('layouts.app')

@section('title', 'Roles & Users - MenetZero')
@section('page-title', 'Roles & Users')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Roles List Section -->
    <div class="mb-12">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Roles List</h1>
            <p class="mt-2 text-gray-600">A role provided access to predefined menus and features so that depending on assigned role an administrator can have access to what user needs.</p>
        </div>

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
            <div class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-6 flex flex-col items-center justify-center hover:border-blue-500 transition-colors">
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

    <!-- Total users with their roles Section -->
    <div class="mb-8">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Total users with their roles</h2>
            <p class="mt-2 text-gray-600">Find all of your company's administrator accounts and their associate roles.</p>
        </div>

        <!-- Toolbar -->
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Items per page -->
                    <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <!-- Search -->
                    <div class="relative">
                        <input type="text" placeholder="Search User" 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Invite New User -->
                    <div class="relative">
                        @if($canAddUser['allowed'])
                            <button onclick="openAddUserModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Invite New User
                            </button>
                        @else
                            <button onclick="showUpgradeMessage()" class="px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed text-sm flex items-center gap-2" title="{{ $userLimitMessage }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Invite New User
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USER</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ROLE</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staffMembers as $staff)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-gray-700 font-medium text-sm">
                                                {{ strtoupper(substr($staff->user->name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $staff->user->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $staff->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $staff->companyCustomRole->role_name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('staff.destroy', $staff->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this user from your company?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    <button onclick="viewUser({{ $staff->user->id ?? 0 }}, '{{ $staff->user->name ?? 'N/A' }}', '{{ $staff->user->email ?? 'N/A' }}', '{{ $staff->user->phone ?? 'N/A' }}', '{{ $staff->companyCustomRole->role_name ?? 'N/A' }}', '{{ $staff->is_active ? 'Active' : 'Inactive' }}')" class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="editUserRole({{ $staff->id }}, {{ $staff->company_custom_role_id ?? 0 }})" class="text-gray-600 hover:text-gray-900" title="Edit Role">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">No users found.</p>
                                    <p class="text-xs text-gray-400 mt-1">Invite new users to get started.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Invitations Section -->
    <div class="mb-8">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Pending Invitations</h2>
            <p class="mt-2 text-gray-600">Invitations that have been sent but not yet accepted.</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invited By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invited At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pendingInvitations as $invitation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $invitation->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $roleId = $invitation->company_custom_role_id ?? $invitation->custom_role_id;
                                    $role = $roleId ? \App\Models\CompanyCustomRole::find($roleId) : null;
                                @endphp
                                @if($role)
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">{{ $role->role_name }}</span>
                                @else
                                    <span class="text-sm text-gray-500">No role assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($invitation->inviter)
                                    <div class="text-sm text-gray-900">{{ $invitation->inviter->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $invitation->inviter->email }}</div>
                                @else
                                    <span class="text-sm text-gray-500">Unknown</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invitation->invited_at ? $invitation->invited_at->format('M d, Y') : 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $invitation->invited_at ? $invitation->invited_at->format('h:i A') : '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($invitation->expires_at)
                                    @if($invitation->expires_at->isPast())
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Expired</span>
                                    @else
                                        <div class="text-sm text-gray-900">{{ $invitation->expires_at->format('M d, Y') }}</div>
                                        <div class="text-sm text-gray-500">{{ $invitation->expires_at->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($invitation->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Pending</span>
                                @elseif($invitation->status === 'accepted')
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Accepted</span>
                                @elseif($invitation->status === 'rejected')
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Rejected</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">{{ ucfirst($invitation->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    @if($invitation->status === 'pending')
                                        <a href="{{ route('staff.invitation-success', $invitation->id) }}" 
                                           class="text-blue-600 hover:text-blue-900" 
                                           title="View Invitation Link">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <button onclick="resendInvitation({{ $invitation->id }})" 
                                                class="text-green-600 hover:text-green-900" 
                                                title="Resend Invitation">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                        <button onclick="cancelInvitation({{ $invitation->id }})" 
                                                class="text-red-600 hover:text-red-900" 
                                                title="Cancel Invitation">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">No pending invitations.</p>
                                    <p class="text-xs text-gray-400 mt-1">Invite new users to see them here.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Invite New User</h2>
                <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="{{ route('staff.store') }}" method="POST" id="addUserForm" onsubmit="return submitAddUserForm(event)">
                @csrf
                
                @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="grid grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                   class="w-full px-3 py-2 border {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="user@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">User will set their password when accepting the invitation</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label for="custom_role_id" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                            <select name="custom_role_id" id="custom_role_id" required
                                    class="w-full px-3 py-2 border {{ $errors->has('custom_role_id') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a role</option>
                                @foreach($customRoles as $role)
                                    <option value="{{ $role->id }}" {{ old('custom_role_id') == $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                                @endforeach
                            </select>
                            @error('custom_role_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <div class="flex gap-2">
                                <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="+91">IN (+91)</option>
                                </select>
                                <input type="tel" name="phone" id="phone" 
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Add any additional notes for this invitation...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 mt-6 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeAddUserModal()" class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

// View User Modal
function viewUser(userId, userName, userEmail, userPhone, userRole, userStatus) {
    // Create modal if it doesn't exist
    if (!document.getElementById('viewUserModal')) {
        const modalHTML = `
            <div id="viewUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">User Details</h2>
                            <button onclick="closeViewUserModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                                <p class="text-lg font-semibold text-gray-900" id="viewUserName"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                                <p class="text-lg text-gray-900" id="viewUserEmail"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Phone</label>
                                <p class="text-lg text-gray-900" id="viewUserPhone"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Role</label>
                                <p class="text-lg text-gray-900" id="viewUserRole"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                                <span id="viewUserStatus"></span>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button onclick="closeViewUserModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    document.getElementById('viewUserName').textContent = userName;
    document.getElementById('viewUserEmail').textContent = userEmail;
    document.getElementById('viewUserPhone').textContent = userPhone || 'N/A';
    document.getElementById('viewUserRole').textContent = userRole;
    document.getElementById('viewUserStatus').textContent = userStatus;
    document.getElementById('viewUserStatus').className = userStatus === 'Active' 
        ? 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'
        : 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
    
    document.getElementById('viewUserModal').classList.remove('hidden');
}

function closeViewUserModal() {
    document.getElementById('viewUserModal').classList.add('hidden');
}

// Edit User Role Modal
function editUserRole(userCompanyRoleId, currentRoleId) {
    // Create modal if it doesn't exist
    if (!document.getElementById('editUserRoleModal')) {
        const modalHTML = `
            <div id="editUserRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Edit User Role</h2>
                            <button onclick="closeEditUserRoleModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <form id="editUserRoleForm" method="POST" action="">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label for="editRoleSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                                <select name="company_custom_role_id" id="editRoleSelect" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a role</option>
                                    @foreach($customRoles as $role)
                                        <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center justify-end gap-3 mt-6">
                                <button type="button" onclick="closeEditUserRoleModal()" 
                                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Update Role
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    const form = document.getElementById('editUserRoleForm');
    // Set form action to the correct route
    form.action = '{{ url("/staff") }}/' + userCompanyRoleId + '/role';
    document.getElementById('editRoleSelect').value = currentRoleId;
    
    document.getElementById('editUserRoleModal').classList.remove('hidden');
}

function closeEditUserRoleModal() {
    document.getElementById('editUserRoleModal').classList.add('hidden');
}

function submitAddUserForm(event) {
    // Validate form before submission (invitation only - no password needed)
    const email = document.getElementById('email').value.trim();
    const customRoleId = document.getElementById('custom_role_id').value;
    
    // Validate required fields
    if (!email || !customRoleId) {
        alert('Please fill in all required fields (Email and Role).');
        event.preventDefault();
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        event.preventDefault();
        return false;
    }
    
    // Form will submit normally
    return true;
}

// Close modal on outside click
document.getElementById('addUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddUserModal();
    }
});

// Close view user modal on outside click
document.addEventListener('click', function(e) {
    const viewModal = document.getElementById('viewUserModal');
    if (viewModal && e.target === viewModal) {
        closeViewUserModal();
    }
    
    const editModal = document.getElementById('editUserRoleModal');
    if (editModal && e.target === editModal) {
        closeEditUserRoleModal();
    }
});

// Close modal on successful form submission
@if(session('success'))
    closeAddUserModal();
@endif

// Keep modal open if there are errors
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        openAddUserModal();
    });
@endif

function showUpgradeMessage() {
    const message = @json($userLimitMessage ?? "You have reached your plan limit for users. Please upgrade your subscription to add more users.");
    alert(message);
}

// Resend Invitation
function resendInvitation(invitationId) {
    if (!confirm('Are you sure you want to resend this invitation?')) {
        return;
    }
    
    fetch(`{{ url('/staff/invitations') }}/${invitationId}/resend`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation resent successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to resend invitation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while resending the invitation.');
    });
}

// Cancel Invitation
function cancelInvitation(invitationId) {
    if (!confirm('Are you sure you want to cancel this invitation? This action cannot be undone.')) {
        return;
    }
    
    fetch(`{{ url('/staff/invitations') }}/${invitationId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation cancelled successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to cancel invitation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the invitation.');
    });
}
</script>

@endsection
