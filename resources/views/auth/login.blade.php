@extends('components.auth-layout')

@section('title', 'Login - MIDDLE EAST NET Zero')

@section('content')
    <h1 class="text-3xl font-semibold mb-2" style="color: #111827;">Welcome back!</h1>
    <p class="text-sm mb-8" style="color: #4b5563;">Sign in to MIDDLE EAST NET Zero's platform.</p>

    <div class="mb-6">
        <a class="btn btn-ghost btn-full" href="{{ route('auth.google') }}">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5"> 
            <span>Continue with Google</span>
        </a>
    </div>

    <div class="flex items-center gap-4 text-xs text-gray-400 mb-6">
        <div class="h-px flex-1 bg-gray-200"></div>
        <span>or</span>
        <div class="h-px flex-1 bg-gray-200"></div>
    </div>

    <form class="space-y-6" method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Business Email</label>
            <input class="form-input" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email...">
        </div>
        <div class="form-group">
            <div class="flex items-center justify-between mb-2">
                <label class="form-label">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm" style="color: var(--brand-primary);">Forgot Password?</a>
            </div>
            <input class="form-input" type="password" name="password" required placeholder="Enter your password...">
        </div>
        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif
        <button type="submit" class="btn btn-primary btn-full">Login</button>
    </form>

    <p class="mt-6 text-sm text-gray-600 text-center">
        Don't have an account? 
        <a href="{{ route('register') }}" class="font-medium" style="color: var(--brand-primary);">Sign up</a>
    </p>
@endsection

@section('sidebar-content')
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Meet MIDDLE EAST NET Zero's platform</span>
    <ul class="mt-6 space-y-4 text-white/90">
        <li class="flex gap-3"><span>✓</span> Understand your emissions</li>
        <li class="flex gap-3"><span>✓</span> Identify carbon hotspots</li>
        <li class="flex gap-3"><span>✓</span> Simulate and compare reductions</li>
        <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
    </ul>
@endsection


