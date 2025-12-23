@extends('admin.layouts.app')

@section('title', 'User Details | MENetZero')
@section('page-title', 'User Details')

@section('content')
    @isset($user)
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">User Information</h2>
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
                        <dt class="font-medium text-gray-500">Primary Company</dt>
                        <dd class="text-gray-900">{{ optional($user->company)->name ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Accessible Companies</h2>
                @if($user->accessibleCompanies->isEmpty())
                    <p class="text-sm text-gray-500">No additional company access.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($user->accessibleCompanies as $ctx)
                            <li class="py-2">
                                <div class="text-gray-900">
                                    {{ optional($ctx->company)->name ?? 'Unknown company' }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Active Context</h2>
                @php $activeCompany = optional($user->activeContext)->activeCompany; @endphp
                @if(!$activeCompany)
                    <p class="text-sm text-gray-500">No active company context stored.</p>
                @else
                    <p class="text-sm text-gray-900">
                        Active company: {{ $activeCompany->name }}
                    </p>
                @endif
            </div>
        </div>
    @else
        <p class="text-gray-500 text-sm">User data not available.</p>
    @endisset
@endsection


