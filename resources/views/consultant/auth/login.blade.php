@extends('layouts.public')

@section('title', 'Consultant Sign In — MENetZero')

@section('content')
<section class="mkt-section">
    <div class="mkt-container max-w-md">
        <div class="mkt-section-head mb-8">
            <div class="mkt-tagline mb-3">For consultants</div>
            <h2>Consultant sign in</h2>
            <p>Access your agency hub, directory profile, and client leads. Agency pack pricing is available after you sign in.</p>
        </div>

        <div class="mkt-feature-card mb-4" style="padding:1.25rem;">
            <a href="{{ route('consultant.auth.google') }}" class="mkt-btn mkt-btn-outline mkt-btn-block" style="gap:0.5rem;">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
                Continue with Google
            </a>
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-400 mb-4 max-w-md mx-auto">
            <div class="h-px flex-1 bg-gray-200"></div>
            <span>or email</span>
            <div class="h-px flex-1 bg-gray-200"></div>
        </div>

        <form method="POST" action="{{ route('consultant.login.post') }}" class="mkt-feature-card space-y-4" style="padding:1.5rem;">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="mkt-form-input">
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="mkt-form-input">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
            <button type="submit" class="mkt-btn mkt-btn-primary mkt-btn-block">Sign in</button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-4">
            New consultant? <a href="{{ route('consultant.register') }}" class="mkt-text-brand hover:underline">Create account</a>
        </p>
        <p class="text-center text-xs text-gray-400 mt-3">
            Tracking emissions for your own company?
            <a href="{{ route('login') }}" class="mkt-text-brand hover:underline">Company sign in</a>
        </p>
    </div>
</section>
@endsection
