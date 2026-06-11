@extends('layouts.portal-auth')

@section('title', 'Company Sign Up — MENetZero')

@section('content')
<p class="auth-eyebrow">For companies</p>
<h1 class="auth-title">Company sign up</h1>
<p class="auth-lead">Create an account to track and report your organisation's carbon emissions.</p>

<a href="{{ route('auth.google') }}" class="btn btn-secondary btn-full">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
    Continue with Google
</a>

<div class="auth-divider">or</div>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="name">Full name</label>
        <input class="form-input" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Enter your full name">
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="email">Business email</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="Enter your email">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-group mb-0">
            <label class="form-label" for="password">Password</label>
            <input class="form-input" id="password" name="password" type="password" required placeholder="Create password">
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group mb-0">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required placeholder="Repeat password">
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Create account</button>
</form>

<p class="auth-footer">
    Already have an account?
    <a href="{{ route('login') }}" class="text-brand font-semibold hover:underline">Company sign in</a>
</p>
<p class="auth-footer-sub">
    Sustainability consultant?
    <a href="{{ route('consultant.register') }}" class="text-brand font-semibold hover:underline">Consultant sign up</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Meet MENetZero's platform</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Understand your product's emissions</li>
    <li class="flex gap-3"><span>✓</span> Gain clarity on carbon hotspots</li>
    <li class="flex gap-3"><span>✓</span> Test changes before production</li>
    <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
</ul>
@endsection
