@extends('layouts.portal-auth')

@section('title', 'Forgot Password — MENetZero')

@section('content')
<h1 class="auth-title">Forgot password?</h1>
<p class="auth-lead">No worries — we'll send you reset instructions.</p>

@if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="email">Business email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-full">Send reset link</button>
</form>

<p class="auth-footer">
    <a href="{{ route('login') }}" class="text-brand font-semibold hover:underline">← Back to sign in</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Secure password reset</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Secure password reset process</li>
    <li class="flex gap-3"><span>✓</span> Email verification required</li>
    <li class="flex gap-3"><span>✓</span> Quick and easy recovery</li>
    <li class="flex gap-3"><span>✓</span> Your account stays protected</li>
</ul>
@endsection
