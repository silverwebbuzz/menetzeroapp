{{-- MENetZero 2.0 — consultant sign up (Phase 1). Contract unchanged:
     name, company_name, email, phone, password, password_confirmation. --}}
@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Consultant Sign Up — MENetZero')

@section('content')
    <h1 class="auth-title">Join as a consultant</h1>
    <p class="auth-lead">List your agency, manage client inventories and receive marketplace work.</p>

    <div style="margin-top:26px">
        <a href="{{ route('consultant.auth.google') }}" class="btn btn-secondary btn-full">
            <svg viewBox="0 0 18 18" style="width:16px;height:16px;flex-shrink:0"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 01-1.8 2.72v2.26h2.91c1.7-1.57 2.69-3.88 2.69-6.62z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.26c-.81.54-1.84.86-3.05.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.34A9 9 0 009 18z"/><path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 010-3.44V4.94H.96a9 9 0 000 8.12l3.01-2.34z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A9 9 0 00.96 4.94l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/></svg>
            Continue with Google
        </a>
        <div class="auth-divider">or</div>
    </div>

    <form method="POST" action="{{ route('consultant.register.post') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Full name</label>
            <input class="form-input" id="name" type="text" name="name"
                   value="{{ old('name') }}" required placeholder="Your name">
        </div>

        <div class="form-group">
            <label class="form-label" for="company_name">Agency name</label>
            <input class="form-input" id="company_name" type="text" name="company_name"
                   value="{{ old('company_name') }}" required placeholder="Your consultancy">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Work email</label>
            <input class="form-input" id="email" type="email" name="email"
                   value="{{ old('email') }}" required placeholder="you@agency.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-input" id="phone" type="text" name="phone"
                   value="{{ old('phone') }}" placeholder="+971 …">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-input" id="password" type="password" name="password"
                   required placeholder="Create a password">
            <span style="font-size:12px;color:#8b9199">At least 8 characters.</span>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-input" id="password_confirmation" type="password"
                   name="password_confirmation" required placeholder="Repeat your password">
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn btn-full">Create consultant account</button>
    </form>

    <p class="auth-footer">
        Already registered? <a href="{{ route('consultant.login') }}">Consultant sign in</a>
    </p>
    <p class="auth-footer-sub">
        Reporting for your own company? <a href="{{ route('register') }}">Company sign up</a>
    </p>
@endsection

@section('sidebar')
    <span>After you register</span>
    <p>Review</p>
    <ul>
        <li>Submit your profile and supporting documents</li>
        <li>Our team verifies credentials before you go live</li>
        <li>Approved agencies appear in the public directory</li>
    </ul>
@endsection
