<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MIDDLE EAST NET Zero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app-shell.css') }}">
    <style>
        .brand-gradient {
            background: linear-gradient(135deg, #059669 0%, #10b981 60%, #34d399 100%);
            position: relative;
            overflow: hidden;
        }
        .brand-gradient::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 40%);
            pointer-events: none;
        }
        .glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
        }
        /* Slightly different auth form styling — larger, more breathing room */
        .auth-form .form-group { margin-bottom: 1.125rem; }
        .auth-form .form-label { font-size: 0.8125rem; font-weight: 500; color: #0f172a; margin-bottom: 0.375rem; }
        .auth-form .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            color: #0f172a;
            background: #ffffff;
            transition: border-color 0.12s, box-shadow 0.12s;
            box-shadow: 0 1px 2px 0 rgb(15 23 42 / 0.04);
        }
        .auth-form .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgb(16 185 129 / 0.15);
        }
        .btn-full { width: 100%; }
    </style>
    @stack('head')
</head>
<body>
    <div class="grid lg:grid-cols-2 min-h-screen">
        <div class="flex items-center justify-center p-6 sm:p-10 bg-white">
            <div class="w-full max-w-md auth-form">
                <div class="mb-8">
                    <a href="/" class="brand-logo">
                        <img src="{{ asset('images/menetzero.svg') }}" alt="MIDDLE EAST NET Zero">
                    </a>
                </div>
                @yield('content')
            </div>
        </div>

        <div class="relative brand-gradient text-white hidden lg:block">
            <div class="relative h-full w-full flex items-center justify-center p-10">
                <div class="glass p-8 w-full max-w-xl">
                    @yield('sidebar-content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>
