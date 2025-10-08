@extends('components.auth-layout')

@section('title', 'Sign up - MIDDLE EAST NET Zero')

@section('content')
    <h1 class="text-3xl font-semibold text-gray-900 mb-2">Sign up for MIDDLE EAST NET Zero</h1>
    <p class="text-sm text-gray-600 mb-8">Measure your organization's carbon footprint with ease.</p>

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

    <form class="space-y-6" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input class="form-input" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Enter your full name">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Business Email</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="Enter your email">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input class="form-input" id="password" name="password" type="password" required placeholder="Create password">
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required placeholder="Repeat password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Create Account</button>
    </form>

    <p class="mt-6 text-sm text-gray-600 text-center">
        Already have an account? 
        <a href="{{ route('login') }}" class="font-medium" style="color: var(--brand-primary);">Sign in</a>
    </p>
@endsection

@section('sidebar-content')
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Meet MIDDLE EAST NET Zero's platform</span>
    <ul class="mt-6 space-y-4 text-white/90">
        <li class="flex gap-3"><span>✓</span> Understand your product's emissions</li>
        <li class="flex gap-3"><span>✓</span> Gain clarity on carbon hotspots</li>
        <li class="flex gap-3"><span>✓</span> Test changes before production</li>
        <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
    </ul>
@endsection
