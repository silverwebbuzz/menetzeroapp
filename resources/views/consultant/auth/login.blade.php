@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Consultant Sign In — MENetZero')

@section('content')
<p class="auth-eyebrow">For consultants</p>
<h1 class="auth-title">Consultant sign in</h1>
<p class="auth-lead">Access your agency hub, directory profile, and client leads. Agency pack pricing is available after you sign in.</p>

<a href="{{ route('consultant.auth.google') }}" class="btn btn-secondary btn-full">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
    Continue with Google
</a>

<div class="auth-divider">or email</div>

<form method="POST" action="{{ route('consultant.login.post') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" id="password" type="password" name="password" required placeholder="Enter your password">
    </div>
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="remember" value="1"> Remember me
    </label>
    <button type="submit" class="btn btn-primary btn-full">Sign in</button>
</form>

<p class="auth-footer">
    New consultant?
    <a href="{{ route('consultant.register') }}" class="text-brand font-semibold hover:underline">Create account</a>
</p>
<p class="auth-footer-sub">
    Tracking emissions for your own company?
    <a href="{{ route('login') }}" class="text-brand font-semibold hover:underline">Company sign in</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Consultant agency hub</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Manage multiple client workspaces</li>
    <li class="flex gap-3"><span>✓</span> MOCCAE-ready inventories and exports</li>
    <li class="flex gap-3"><span>✓</span> Verified directory listing</li>
    <li class="flex gap-3"><span>✓</span> Wholesale agency pack pricing</li>
</ul>
@endsection
