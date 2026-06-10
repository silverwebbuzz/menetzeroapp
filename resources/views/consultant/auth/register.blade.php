@extends('layouts.public')

@section('title', 'Consultant Registration — MENetZero')

@section('content')
<section class="mkt-section">
    <div class="mkt-container max-w-md">
        <div class="mkt-section-head mb-8">
            <h2>Apply to the consultant directory</h2>
            <p>Create your consultant account. You can upload documents after signing in.</p>
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
    </div>
</section>
@endsection
