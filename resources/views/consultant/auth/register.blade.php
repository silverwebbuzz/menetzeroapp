@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Consultant Registration — MENetZero')

@section('content')
<p class="auth-eyebrow">For consultants</p>
<h1 class="auth-title">Create consultant account</h1>
<p class="auth-lead">Register your practice for the directory and agency hub. Pack pricing is shown after you sign in.</p>

<a href="{{ route('consultant.auth.google') }}" class="btn btn-secondary btn-full">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
    Sign up with Google
</a>

<div class="auth-divider">or email</div>

<form method="POST" action="{{ route('consultant.register.post') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="name">Your name</label>
        <input class="form-input" id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Your full name">
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="company_name">Practice / company name</label>
        <input class="form-input" id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" required placeholder="Your practice or company">
        @error('company_name')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="phone">Phone (optional)</label>
        <input class="form-input" id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone number">
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" id="password" type="password" name="password" required placeholder="Create password">
        @error('password')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" required placeholder="Repeat password">
    </div>
    <button type="submit" class="btn btn-primary btn-full">Create consultant account</button>
</form>

<p class="auth-footer">
    Already registered?
    <a href="{{ route('consultant.login') }}" class="text-brand font-semibold hover:underline">Sign in</a>
</p>
<p class="auth-footer-sub">
    Need plans for your own company?
    <a href="{{ route('register') }}" class="text-brand font-semibold hover:underline">Company sign up</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Start with a free trial client</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> One login for all client workspaces</li>
    <li class="flex gap-3"><span>✓</span> Directory profile for SME leads</li>
    <li class="flex gap-3"><span>✓</span> IFRS / GRI reporting tools</li>
    <li class="flex gap-3"><span>✓</span> Scale with agency packs when ready</li>
</ul>
@endsection
