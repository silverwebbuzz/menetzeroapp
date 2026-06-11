@extends('layouts.portal-auth')

@section('title', 'Company Sign In — MENetZero')

@section('content')
<p class="auth-eyebrow">For companies</p>
<h1 class="auth-title">Company sign in</h1>
<p class="auth-lead">Sign in to track and report your organisation's carbon emissions.</p>

<a href="{{ route('auth.google') }}" class="btn btn-secondary btn-full mb-0">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
    Continue with Google
</a>

<div class="auth-divider">or</div>

<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <div class="form-group mb-0">
        <label class="form-label" for="email">Business email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
    </div>
    <div class="form-group mb-0">
        <div class="flex items-center justify-between mb-1">
            <label class="form-label mb-0" for="password">Password</label>
            <a href="{{ route('password.request') }}" class="text-sm text-brand hover:underline">Forgot password?</a>
        </div>
        <input class="form-input" id="password" type="password" name="password" required placeholder="Enter your password">
    </div>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <button type="submit" class="btn btn-primary btn-full">Sign in</button>
</form>

<p class="auth-footer">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-brand font-semibold hover:underline">Company sign up</a>
</p>
<p class="auth-footer-sub">
    Sustainability consultant?
    <a href="{{ route('consultant.login') }}" class="text-brand font-semibold hover:underline">Consultant sign in</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Meet MENetZero's platform</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Understand your emissions</li>
    <li class="flex gap-3"><span>✓</span> Identify carbon hotspots</li>
    <li class="flex gap-3"><span>✓</span> Simulate and compare reductions</li>
    <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
</ul>
@endsection
