@extends('layouts.public')

@section('title', 'Consultant Registration — MENetZero')

@section('content')
<section class="mkt-section">
    <div class="mkt-container max-w-md">
        <div class="mkt-section-head mb-8">
            <div class="mkt-tagline mb-3">For consultants</div>
            <h2>Create consultant account</h2>
            <p>Register your practice for the directory and agency hub. Pack pricing is shown after you sign in.</p>
        </div>

        <div class="mkt-feature-card mb-4" style="padding:1.25rem;">
            <a href="{{ route('consultant.auth.google') }}" class="mkt-btn mkt-btn-outline mkt-btn-block" style="gap:0.5rem;">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5">
                Sign up with Google
            </a>
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-400 mb-4 max-w-md mx-auto">
            <div class="h-px flex-1 bg-gray-200"></div>
            <span>or email</span>
            <div class="h-px flex-1 bg-gray-200"></div>
        </div>

        <form method="POST" action="{{ route('consultant.register.post') }}" class="mkt-feature-card space-y-4" style="padding:1.5rem;">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="mkt-form-input">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Practice / company name</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" required class="mkt-form-input">
                @error('company_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="mkt-form-input">
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="mkt-form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="mkt-form-input">
                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required class="mkt-form-input">
            </div>
            <button type="submit" class="mkt-btn mkt-btn-primary mkt-btn-block">Create consultant account</button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-4">
            Already registered? <a href="{{ route('consultant.login') }}" class="mkt-text-brand hover:underline">Sign in</a>
        </p>
        <p class="text-center text-xs text-gray-400 mt-3">
            Need plans for your own company?
            <a href="{{ route('register') }}" class="mkt-text-brand hover:underline">Company sign up</a>
        </p>
    </div>
</section>
@endsection
