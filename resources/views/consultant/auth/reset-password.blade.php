@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Choose a New Password — MENetZero')

@section('content')
<p class="auth-eyebrow">For consultants</p>
<h1 class="auth-title">Choose a new password</h1>
<p class="auth-lead">Pick a password of at least 8 characters. You'll use it to sign in to your agency hub.</p>

<form method="POST" action="{{ route('consultant.password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group mb-0">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" type="email" name="email" value="{{ old('email', $email) }}" required readonly>
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="password">New password</label>
        <input class="form-input" id="password" type="password" name="password" required autofocus placeholder="At least 8 characters">
        @error('password')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group mb-0">
        <label class="form-label" for="password_confirmation">Confirm new password</label>
        <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" required placeholder="Re-enter your new password">
    </div>
    <button type="submit" class="btn btn-primary btn-full">Reset password</button>
</form>

<p class="auth-footer">
    <a href="{{ route('consultant.login') }}" class="text-brand font-semibold hover:underline">Back to sign in</a>
</p>
@endsection

@section('sidebar')
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold">Consultant agency hub</span>

<p class="mt-5 mb-1 text-xs font-bold uppercase tracking-wider text-white/60">Almost done</p>
<ul class="space-y-2.5 text-white/90 text-base font-medium">
    <li class="flex gap-3"><span>✓</span> Set your new password to regain access</li>
    <li class="flex gap-3"><span>✓</span> Your managed client workspaces stay intact</li>
    <li class="flex gap-3"><span>✓</span> You'll be signed out of the reset link once used</li>
</ul>
@endsection
