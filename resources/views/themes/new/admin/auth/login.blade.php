{{--
    MENetZero 2.0 — admin sign in (Phase 1).

    Overrides admin/auth/login.blade.php under the new theme only. The
    original is untouched and still renders for the old theme.

    Same form contract as the original: POST to admin.login.post with
    email, password and an optional remember checkbox.
--}}
@extends('layouts.portal-auth', ['portalVariant' => 'admin'])

@section('title', 'Admin Sign In — MENetZero')

@section('content')
    <h1 class="auth-title">Platform sign in</h1>
    <p class="auth-lead">Administrative access to companies, consultants, subscriptions and platform data.</p>

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="admin-email">Admin email</label>
            <input class="form-input" id="admin-email" type="email" name="email"
                   value="{{ old('email') }}" required autofocus
                   placeholder="you@menetzero.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="admin-password">Password</label>
            <input class="form-input" id="admin-password" type="password" name="password"
                   required placeholder="Enter your password">
        </div>

        <label style="display:flex;align-items:center;gap:9px;font-size:12.5px;color:#5a6068">
            <input type="checkbox" name="remember" value="1" style="width:15px;height:15px">
            Keep me signed in on this device
        </label>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn btn-full">Sign in</button>
    </form>

    <p class="auth-footer">
        <a href="{{ route('login') }}">Back to company &amp; consultant sign in</a>
    </p>
@endsection

@section('sidebar')
    <span>Platform administration</span>

    <p>Operate</p>
    <ul>
        <li>Companies, users and team access</li>
        <li>Consultant directory, approvals and escrow orders</li>
        <li>Subscription plans, price book and package requests</li>
    </ul>

    <p>Maintain</p>
    <ul>
        <li>Emission sources, factors, GWP values and unit conversions</li>
        <li>Site content, email templates and payment gateways</li>
    </ul>
@endsection
