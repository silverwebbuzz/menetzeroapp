@extends('components.auth-layout')

@section('title', 'Forgot Password - MIDDLE EAST NET Zero')

@section('content')
    <h1 class="text-3xl font-semibold text-gray-900 mb-2">Forgot Password?</h1>
    <p class="text-sm text-gray-600 mb-8">No worries, we'll send you reset instructions.</p>

    @if (session('status'))
        <div class="alert alert-success mb-6">
            {{ session('status') }}
        </div>
    @endif

    <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Business Email</label>
            <input class="form-input" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email...">
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm font-medium" style="color: var(--brand-primary);">Back to Login</a>
    </div>
@endsection

@section('sidebar-content')
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Secure Password Reset</span>
    <ul class="mt-6 space-y-4 text-white/90">
        <li class="flex gap-3"><span>✓</span> Secure password reset process</li>
        <li class="flex gap-3"><span>✓</span> Email verification required</li>
        <li class="flex gap-3"><span>✓</span> Quick and easy recovery</li>
        <li class="flex gap-3"><span>✓</span> Your account stays protected</li>
    </ul>
@endsection
