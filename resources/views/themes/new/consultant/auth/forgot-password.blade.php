{{-- MENetZero 2.0 — consultant forgot password (Phase 1). Consultants use
     their own broker + token table (see config/auth.php). --}}
@php($portalVariant = 'consultant')
@extends('layouts.portal-auth')

@section('title', 'Reset Consultant Password — MENetZero')

@section('content')
    <h1 class="auth-title">Reset your password</h1>
    <p class="auth-lead">Enter the email your consultant account uses and we'll send a reset link.</p>

    @if (session('status'))
        <div class="alert alert-success" style="margin-top:22px">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('consultant.password.email') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Work email</label>
            <input class="form-input" id="email" type="email" name="email"
                   value="{{ old('email') }}" required placeholder="you@agency.com">
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn btn-full">Send reset link</button>
    </form>

    <p class="auth-footer">
        Remembered it? <a href="{{ route('consultant.login') }}">Back to consultant sign in</a>
    </p>
    <p class="auth-footer-sub">
        Company account? <a href="{{ route('password.request') }}">Reset a company password</a>
    </p>
@endsection

@section('sidebar')
    <span>Account recovery</span>
    <p>What happens next</p>
    <ul>
        <li>We email a single-use reset link to your address</li>
        <li>Consultant accounts use a separate reset flow from company accounts</li>
        <li>Your managed client workspaces are unaffected</li>
    </ul>
@endsection
