{{-- MENetZero 2.0 — consultant set new password (Phase 1). Contract
     unchanged: token, email, password, password_confirmation. --}}
@php($portalVariant = 'consultant')
@extends($authLayout ?? 'layouts.portal-auth')

@section('title', 'Set New Password — MENetZero')

@section('content')
    <h1 class="auth-title">Set a new password</h1>
    <p class="auth-lead">Choose a new password for your consultant account.</p>

    <form method="POST" action="{{ route('consultant.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">

        <div class="form-group">
            <label class="form-label" for="email">Work email</label>
            <input class="form-input" id="email" type="email" name="email"
                   value="{{ old('email', $email ?? request('email')) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">New password</label>
            <input class="form-input" id="password" type="password" name="password"
                   required placeholder="Create a new password">
            <span style="font-size:12px;color:#8b9199">At least 8 characters.</span>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input class="form-input" id="password_confirmation" type="password"
                   name="password_confirmation" required placeholder="Repeat your new password">
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn btn-full">Update password</button>
    </form>

    <p class="auth-footer"><a href="{{ route('consultant.login') }}">Back to consultant sign in</a></p>
@endsection

@section('sidebar')
    <span>Choosing a strong password</span>
    <p>Guidance</p>
    <ul>
        <li>Longer beats complex — aim for 12 characters or more</li>
        <li>Do not reuse a password from another service</li>
        <li>Client workspace access is unchanged by this reset</li>
    </ul>
@endsection
