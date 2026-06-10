@extends('layouts.public')

@section('title', 'Consultant Sign In — MENetZero')

@section('content')
<section class="mkt-section">
    <div class="mkt-container max-w-md">
        <div class="mkt-section-head mb-8">
            <h2>Consultant sign in</h2>
            <p>Access your agency hub, directory profile, and client leads.</p>
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
            New consultant? <a href="{{ route('consultant.register') }}" class="mkt-text-brand hover:underline">Apply here</a>
        </p>
    </div>
</section>
@endsection
