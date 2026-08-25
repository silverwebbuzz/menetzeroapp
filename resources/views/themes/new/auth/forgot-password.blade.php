{{-- MENetZero 2.0 — forgot password (Phase 1). Form contract unchanged:
     POST password.email with an email field. --}}
@extends($authLayout ?? 'layouts.portal-auth')

@section('title', 'Reset Password — MENetZero')

@section('content')
    <p class="auth-eyebrow">Account recovery</p>
    <h1 class="auth-title">Reset your password</h1>
    <p class="auth-lead">Enter the email you sign in with and we'll send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-success" style="margin-top:22px">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Business email</label>
            <input class="form-input" id="email" type="email" name="email"
                   value="{{ old('email') }}" required placeholder="you@company.com">
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn btn-full">Send reset link</button>
    </form>

    <p class="auth-footer">
        Remembered it? <a href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection

@section('sidebar')
    <span>Account recovery</span>
    <p>What happens next</p>
    <ul>
        <li>We email a single-use reset link to your address</li>
        <li>The link expires after 60 minutes</li>
        <li>Your existing password keeps working until you set a new one</li>
    </ul>
@endsection
