@extends('layouts.app')

@section('title', 'Set Your Password - MenetZero')

@section('content')
<div class="max-w-2xl mx-auto py-12">
    <div class="bg-white rounded-lg border border-gray-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Set Your Password</h1>
            <p class="mt-2 text-gray-600">Welcome to {{ $invitation->company->name ?? 'the company' }}!</p>
            <p class="mt-1 text-sm text-gray-500">Please create a password to complete your account setup.</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-700">Email:</span>
                    <span class="text-sm text-gray-900">{{ $email }}</span>
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
            </div>
        </div>

        <form action="{{ route('invitations.setup-password.process', ['token' => $token, 'password_token' => $passwordToken]) }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required
                           minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('password') border-red-500 @enderror"
                           placeholder="Enter your password (min. 8 characters)">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           required
                           minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('password_confirmation') border-red-500 @enderror"
                           placeholder="Confirm your password">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        @foreach($errors->all() as $error)
                            <p class="text-sm text-red-600">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <button type="submit" 
                        class="w-full px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium">
                    Set Password & Continue
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

