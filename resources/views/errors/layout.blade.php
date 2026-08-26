{{--
    Shared error-page shell (Phase 5.8).

    STANDALONE BY DESIGN — this deliberately does NOT @extends layouts.app.

    layouts/app.blade.php calls auth()->user()->isAdmin() unguarded (line ~339)
    and resolves the active company. An error page rendered for a GUEST — a 404
    on a public URL, or a 419 after the session expired, which is precisely when
    the user is logged out — would throw a SECOND fatal error while rendering the
    first. The result is a blank white page or an infinite error loop.

    So this shell has no auth calls, no composers, no external CSS or JS, and no
    CDN dependency. All styling is inline. It renders identically whether the
    request is authenticated or not, and whether or not the theme session exists.

    It is theme-neutral: both the old and new themes use it. The palette is taken
    from mnz-ui.css so it does not look foreign in the new theme, and it is close
    enough to the emerald brand not to look foreign in the old one.

    Slots: @section('code') @section('heading') @section('message')
           @section('actions') — optional, falls back to a Dashboard/Home link.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('heading') — MENetZero</title>
    <link rel="icon" href="{{ asset('images/menetzero.svg') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f7f7f5;
            color: #14161a;
            font: 400 14px/1.55 ui-sans-serif, system-ui, -apple-system,
                  "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .err-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .err-card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid #e4e4e1;
            border-radius: 4px;
            padding: 40px 36px;
            text-align: center;
        }
        .err-logo { height: 30px; width: auto; margin-bottom: 28px; }
        .err-code {
            font: 500 11px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #8b8f96;
        }
        .err-heading {
            font-size: 21px;
            font-weight: 600;
            letter-spacing: -.02em;
            margin: 12px 0 0;
        }
        .err-message {
            font-size: 13.5px;
            color: #5b6068;
            margin: 12px auto 0;
            max-width: 42ch;
            text-wrap: pretty;
        }
        .err-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 18px;
            border: 1px solid #d8d8d4;
            border-radius: 3px;
            background: #fff;
            color: #14161a;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }
        .err-btn:hover { background: #f2f2ef; }
        .err-btn--primary {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }
        .err-btn--primary:hover { background: #115e57; color: #fff; }
        .err-foot {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #8b8f96;
        }
        .err-foot a { color: #5b6068; }
        @media (prefers-color-scheme: dark) {
            body { background: #16171a; color: #ececea; }
            .err-card { background: #1d1f23; border-color: #2c2f34; }
            .err-message { color: #a8adb5; }
            .err-btn { background: #24262b; border-color: #34373d; color: #ececea; }
            .err-btn:hover { background: #2c2f34; }
            .err-btn--primary { background: #0f766e; border-color: #0f766e; color: #fff; }
        }
    </style>
</head>
<body>
    <div class="err-wrap">
        <div class="err-card">
            <img class="err-logo" src="{{ asset('images/menetzero.svg') }}" alt="MENetZero">

            <div class="err-code">Error @yield('code')</div>
            <h1 class="err-heading">@yield('heading')</h1>
            <p class="err-message">@yield('message')</p>

            <div class="err-actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a class="err-btn err-btn--primary" href="{{ url('/') }}">Go to MENetZero</a>
                @endif
            </div>
        </div>
    </div>

    <div class="err-foot">
        Need help? <a href="mailto:support@menetzero.com">support@menetzero.com</a>
    </div>
</body>
</html>
