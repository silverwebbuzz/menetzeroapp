{{-- MENetZero 2.0 — company sign in (Phase 1). Overrides auth/login.blade.php
     under the new theme. Form contract unchanged: POST login.post, email +
     password, Google SSO via auth.google. --}}
@extends($authLayout ?? 'layouts.portal-auth')

@section('title', 'Company Sign In — MENetZero')

@section('content')
    <p class="auth-eyebrow">For companies</p>
    <h1 class="auth-title">Company sign in</h1>
    <p class="auth-lead">Sign in to track and report your organisation's carbon emissions.</p>

    <div style="margin-top:26px">
        <a href="{{ route('auth.google') }}" class="btn btn-secondary btn-full">
            <svg viewBox="0 0 18 18" style="width:16px;height:16px;flex-shrink:0"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 01-1.8 2.72v2.26h2.91c1.7-1.57 2.69-3.88 2.69-6.62z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.26c-.81.54-1.84.86-3.05.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.34A9 9 0 009 18z"/><path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 010-3.44V4.94H.96a9 9 0 000 8.12l3.01-2.34z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A9 9 0 00.96 4.94l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/></svg>
            Continue with Google
        </a>
        <div class="auth-divider">or</div>
    </div>

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Business email</label>
            <input class="form-input" id="email" type="email" name="email"
                   value="{{ old('email') }}" required placeholder="you@company.com">
        </div>

        <div class="form-group">
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px">
                <label class="form-label" for="password">Password</label>
                <a href="{{ route('password.request') }}" style="font-size:12px;color:#5a6068">Forgot password?</a>
            </div>
            <input class="form-input" id="password" type="password" name="password"
                   required placeholder="Enter your password">
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
                @if (session('consultant_login_url'))
                    <a href="{{ session('consultant_login_url') }}">Consultant sign in</a>
                @endif
            </div>
        @endif

        <button type="submit" class="btn btn-full">Sign in</button>
    </form>

    <p class="auth-footer">
        Don't have an account?
        <a href="{{ route('register') }}">Company sign up</a>
    </p>
    <p class="auth-footer-sub">
        Sustainability consultant?
        <a href="{{ route('consultant.login') }}">Consultant sign in</a>
    </p>
@endsection

@section('sidebar')
    <span>Your carbon accounting workspace</span>

    <p>Measure</p>
    <ul>
        <li>Quick Input for Scope 1, 2 &amp; 3 activity data</li>
        <li>DEWA / ADDC electricity and district cooling</li>
        <li>Fuel, fleet, refrigerants and process emissions</li>
        <li>Multi-location setup with emission boundaries</li>
    </ul>

    <p>Report</p>
    <ul>
        <li>GHG Inventory by year, scope and location</li>
        <li>IFRS S1 &amp; S2, GRI, SASB and UAE ESG Report</li>
        <li>Excel, PDF, MOCCAE and IEQT exports</li>
    </ul>
@endsection
