@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Reset Consultant Password — MENetZero')

@section('content')
<p class="auth-eyebrow">For consultants</p>
<h1 class="auth-title">Forgot your password?</h1>
<p class="auth-lead">Enter the email address on your consultant account and we'll send you a link to choose a new password.</p>

@if (session('status'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('consultant.password.email') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-full">Send reset link</button>
</form>

<p class="auth-footer">
    Remembered it?
    <a href="{{ route('consultant.login') }}" class="text-brand font-semibold hover:underline">Back to sign in</a>
</p>
<p class="auth-footer-sub">
    Tracking emissions for your own company?
    <a href="{{ route('password.request') }}" class="text-brand font-semibold hover:underline">Company password reset</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Consultant agency hub</span>

<p class="mt-5 mb-1 text-xs font-bold uppercase tracking-wider text-white/60">Account recovery</p>
<ul class="space-y-2.5 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Reset links expire after 60 minutes</li>
    <li class="flex gap-3"><span>✓</span> The link can only be used once</li>
    <li class="flex gap-3"><span>✓</span> Your password stays unchanged until you set a new one</li>
</ul>

<p class="mt-5 mb-1 text-xs font-bold uppercase tracking-wider text-white/60">Signed up with Google?</p>
<ul class="space-y-2.5 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Use <strong>Continue with Google</strong> on the sign-in page instead</li>
</ul>
@endsection
