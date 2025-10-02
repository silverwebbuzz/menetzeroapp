<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MenetZero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        .brand-gradient { background-image: radial-gradient(1200px 600px at 80% 20%, rgba(16,185,129,.25), rgba(16,185,129,0)), linear-gradient(135deg, #0ea5a3 0%, #10b981 100%); }
        .glass { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); backdrop-filter: blur(10px); }
        .btn-primary { background-color:#10b981; color:#fff; }
        .btn-primary:hover { background-color:#0ea5a3; }
        .btn-neutral { background:#f8fafc; border:1px solid #e5e7eb; }
        .btn-neutral:hover { background:#f1f5f9; }
        .input { width:100%; padding:.75rem 1rem; border:1px solid #e5e7eb; border-radius:.75rem; outline:none; }
        .input:focus { border-color:#10b981; box-shadow:0 0 0 4px rgba(16,185,129,.15); }
    </style>
</head>
<body class="min-h-screen">
    <div class="grid lg:grid-cols-2 min-h-screen">
        <div class="flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="mb-10">
                    <div class="h-9 w-28 rounded bg-emerald-600/10 text-emerald-700 flex items-center justify-center font-semibold">MenetZero</div>
                </div>
                <h1 class="text-3xl font-semibold text-gray-900">Welcome back!</h1>
                <p class="mt-2 text-sm text-gray-600">Sign in to MenetZero’s platform.</p>

                <div class="mt-8 grid grid-cols-2 gap-3">
                    <a class="btn-neutral rounded-xl px-4 py-2.5 flex items-center justify-center gap-2 text-gray-700" href="#">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5"> Google
                    </a>
                    <a class="btn-neutral rounded-xl px-4 py-2.5 flex items-center justify-center gap-2 text-gray-700" href="#">
                        <img src="https://www.svgrepo.com/show/452093/microsoft.svg" alt="" class="w-5 h-5"> Microsoft
                    </a>
                </div>

                <div class="my-6 flex items-center gap-4 text-xs text-gray-400">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    or
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <form class="space-y-4" method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Business Email</label>
                        <input class="input" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email...">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm text-gray-600">Password</label>
                            <a href="#" class="text-xs text-emerald-700 hover:underline">Forgot Password?</a>
                        </div>
                        <input class="input" type="password" name="password" required placeholder="Enter your password...">
                    </div>
                    @if($errors->any())
                        <p class="text-sm text-rose-600">{{ $errors->first() }}</p>
                    @endif
                    <button type="submit" class="btn-primary w-full rounded-xl py-3 font-medium">Login</button>
                </form>

                <p class="mt-6 text-sm text-gray-600">Don't have an account? <a href="{{ route('register') }}" class="text-emerald-700 font-medium">Sign up</a></p>
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="absolute inset-0" aria-hidden="true"></div>
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass rounded-3xl p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Meet MenetZero’s platform</span>
                    <ul class="mt-6 space-y-4 text-white/90">
                        <li class="flex gap-3"><span>✓</span> Understand your emissions</li>
                        <li class="flex gap-3"><span>✓</span> Identify carbon hotspots</li>
                        <li class="flex gap-3"><span>✓</span> Simulate and compare reductions</li>
                        <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


