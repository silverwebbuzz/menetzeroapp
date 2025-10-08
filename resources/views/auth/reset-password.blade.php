@extends('components.auth-layout')

@section('title', 'Reset Password - MIDDLE EAST NET Zero')

@section('content')
    <h1 class="text-3xl font-semibold text-gray-900 mb-2">Reset Password</h1>
    <p class="text-sm text-gray-600 mb-8">Enter your new password below.</p>

    <form class="space-y-6" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" type="email" name="email" value="{{ $email }}" required readonly>
        </div>
        
        <div class="form-group">
            <label class="form-label">New Password</label>
            <input class="form-input" type="password" name="password" required placeholder="Enter new password...">
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input class="form-input" type="password" name="password_confirmation" required placeholder="Confirm new password...">
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <button type="submit" class="btn btn-primary btn-full">Reset Password</button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm font-medium" style="color: var(--brand-primary);">Back to Login</a>
    </div>
@endsection

@section('sidebar-content')
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Secure Password Reset</span>
    <ul class="mt-6 space-y-4 text-white/90">
        <li class="flex gap-3"><span>✓</span> Choose a strong password</li>
        <li class="flex gap-3"><span>✓</span> At least 8 characters long</li>
        <li class="flex gap-3"><span>✓</span> Mix of letters and numbers</li>
        <li class="flex gap-3"><span>✓</span> Keep it secure and private</li>
    </ul>
@endsection
