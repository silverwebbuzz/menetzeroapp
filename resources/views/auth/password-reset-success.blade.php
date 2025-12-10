@extends('layouts.app')

@section('title', 'Password Reset Link - MenetZero')
@section('page-title', 'Password Reset Link')

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
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Password Reset Link Generated!</h1>
            <p class="mt-2 text-gray-600">A password reset link has been created for <strong>{{ $email }}</strong></p>
        </div>

        <!-- Email Preview -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">📧 Email Preview</h3>
            <p class="text-sm text-gray-700 mb-4">
                The following email would be sent to <strong>{{ $email }}</strong>:
            </p>
            
            <div class="bg-white rounded-lg p-4 border border-blue-200">
                <div class="mb-3">
                    <strong>Subject:</strong> Reset Your Password
                </div>
                <div class="text-sm text-gray-700 space-y-2">
                    <p>Hello,</p>
                    <p>
                        You requested to reset your password. Click the link below to reset it:
                    </p>
                    <div class="my-4">
                        <a href="{{ $resetUrl }}" 
                           class="inline-block px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-center">
                            Reset Password
                        </a>
                    </div>
                    <p class="text-xs text-gray-500">
                        This link will expire in 60 minutes. If you did not request a password reset, please ignore this email.
                    </p>
                </div>
            </div>
        </div>

        <!-- Reset Link -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">🔗 Password Reset Link</h3>
            <p class="text-sm text-gray-700 mb-4">
                Since email is not configured, you can use this link to reset your password:
            </p>
            <div class="bg-white rounded-lg p-4 border border-yellow-200 mb-4">
                <div class="flex items-center gap-2">
                    <input type="text" 
                           value="{{ $resetUrl }}" 
                           readonly
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono"
                           id="reset-link">
                    <button onclick="copyToClipboard()" 
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
                        Copy
                    </button>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ $resetUrl }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Open Reset Link
                </a>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end pt-6 border-t border-gray-200">
            <a href="{{ route('login') }}" 
               class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Back to Login
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const input = document.getElementById('reset-link');
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

