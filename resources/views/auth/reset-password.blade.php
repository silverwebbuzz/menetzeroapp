@extends('layouts.portal-auth')

@section('title', 'Reset Password — MENetZero')

@section('content')
<h1 class="auth-title">Reset password</h1>
<p class="auth-lead">Enter your new password below.</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group mb-0">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ $email }}" required readonly>
    </div>

    <div class="form-group mb-0">
        <label class="form-label" for="password">New password</label>
        <input class="form-input" id="password" type="password" name="password" required placeholder="Enter new password">
        @error('password')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group mb-0">
        <label class="form-label" for="password_confirmation">Confirm new password</label>
        <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" required placeholder="Confirm new password">
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <button type="submit" class="btn btn-primary btn-full">Reset password</button>
</form>

<p class="auth-footer">
    <a href="{{ route('login') }}" class="text-brand font-semibold hover:underline">← Back to sign in</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Secure password reset</span>
<ul class="mt-6 space-y-4 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Choose a strong password</li>
    <li class="flex gap-3"><span>✓</span> At least 8 characters long</li>
    <li class="flex gap-3"><span>✓</span> Mix of letters and numbers</li>
    <li class="flex gap-3"><span>✓</span> Keep it secure and private</li>
</ul>
@endsection
