@extends('layouts.app')

@section('title', 'Invitation Sent - MenetZero')
@section('page-title', 'Invitation Sent Successfully')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-8">
        <!-- Success Icon -->
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Invitation Sent Successfully!</h1>
            <p class="mt-2 text-gray-600">An invitation has been sent to <strong>{{ $invitation->email }}</strong></p>
        </div>

        <!-- Invitation Details -->
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invitation Details</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Email:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->email }}</span>
                </div>
                
                @if($invitation->customRole)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Role:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->customRole->role_name }}</span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Company:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->company->name ?? 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Invited By:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->inviter->name ?? 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Expires:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->expires_at->format('M d, Y h:i A') }}</span>
                </div>
                
                @if($invitation->notes)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Notes:</span>
                    <span class="text-sm text-gray-900">{{ $invitation->notes }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Email Preview -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">📧 Email Preview</h3>
            <p class="text-sm text-gray-700 mb-4">
                The following email would be sent to <strong>{{ $invitation->email }}</strong>:
            </p>
            
            <div class="bg-white rounded-lg p-4 border border-blue-200">
                <div class="mb-3">
                    <strong>Subject:</strong> Invitation to join {{ $invitation->company->name ?? 'the company' }}
                </div>
                <div class="text-sm text-gray-700 space-y-2">
                    <p>Hello,</p>
                    <p>
                        You have been invited by <strong>{{ $invitation->inviter->name ?? 'a team member' }}</strong> 
                        to join <strong>{{ $invitation->company->name ?? 'the company' }}</strong> 
                        @if($invitation->customRole)
                        as a <strong>{{ $invitation->customRole->role_name }}</strong>.
                        @endif
                    </p>
                    <p>Click the link below to accept the invitation:</p>
                    <div class="my-4">
                        <a href="{{ $acceptUrl }}" 
                           class="inline-block px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-center">
                            Accept Invitation
                        </a>
                    </div>
                    <p class="text-xs text-gray-500">
                        This invitation will expire on {{ $invitation->expires_at->format('M d, Y') }}.
                    </p>
                    @if($invitation->notes)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-600"><strong>Note:</strong> {{ $invitation->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Test Link -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">🔗 Test Invitation Link</h3>
            <p class="text-sm text-gray-700 mb-4">
                Since email is not configured, you can use this link to test the invitation acceptance:
            </p>
            <div class="bg-white rounded-lg p-4 border border-yellow-200 mb-4">
                <div class="flex items-center gap-2">
                    <input type="text" 
                           value="{{ $acceptUrl }}" 
                           readonly
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono"
                           id="invitation-link">
                    <button onclick="copyToClipboard()" 
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
                        Copy
                    </button>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ $acceptUrl }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Open Invitation Link
                </a>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <a href="{{ route('staff.index') }}" 
               class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Back to Staff List
            </a>
            <a href="{{ route('staff.create') }}" 
               class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Invite Another Staff Member
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const input = document.getElementById('invitation-link');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    
    // Show feedback
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Copied!';
    button.classList.add('bg-green-600');
    button.classList.remove('bg-orange-600');
    
    setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('bg-green-600');
        button.classList.add('bg-orange-600');
    }, 2000);
}
</script>
@endsection

