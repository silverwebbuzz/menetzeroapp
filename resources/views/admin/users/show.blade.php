@extends('admin.layouts.app')

@section('title', 'User Details | MENetZero')
@section('page-title', 'User Details')

@section('content')
    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">User Information</h2>
            @isset($user)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Name</dt>
                        <dd class="text-gray-900">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Role</dt>
                        <dd class="text-gray-900">{{ $user->role ?? 'user' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Company</dt>
                        <dd class="text-gray-900">{{ optional($user->company)->name ?? 'N/A' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-gray-500 text-sm">User data not available.</p>
            @endisset
        </div>
    </div>
@endsection


