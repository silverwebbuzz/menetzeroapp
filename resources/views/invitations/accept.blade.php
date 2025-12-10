@extends('layouts.app')

@section('title', 'Accept Invitation - MenetZero')

@section('content')
<div class="max-w-2xl mx-auto py-12">
    <div class="bg-white rounded-lg border border-gray-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">You've Been Invited!</h1>
            <p class="mt-2 text-gray-600">Join {{ $invitation->company->name ?? 'the company' }}</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invitation Details</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Email:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->email }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Company:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->company->name ?? 'N/A' }}</span>
                </div>
                
                @if($invitation->customRole)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Role:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->customRole->role_name }}</span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Expires:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->expires_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        @if(isset($user) && $user)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                You are currently logged in as <strong>{{ $user->email }}</strong>. 
                @if($user->email === $invitation->email)
                    You can accept this invitation directly.
                @else
                    Please log out and log in with {{ $invitation->email }} to accept this invitation.
                @endif
            </p>
        </div>
        @endif

        <form action="{{ route('invitations.accept.process', $invitation->token) }}" method="POST">
            @csrf
            
            <div class="flex gap-4 justify-center">
                <button type="submit" 
                        name="action" 
                        value="accept"
                        class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Accept Invitation
                </button>
                <button type="submit" 
                        name="action" 
                        value="decline"
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Decline
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

