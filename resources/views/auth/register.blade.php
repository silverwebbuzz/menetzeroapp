<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - MenetZero</title>
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
                <h1 class="text-3xl font-semibold text-gray-900">Sign up for MenetZero</h1>
                <p class="mt-2 text-sm text-gray-600">Measure your organization’s carbon footprint with ease.</p>

                <div class="mt-8">
                    <a class="btn-neutral rounded-xl px-6 py-3 flex items-center justify-center gap-3 text-gray-700 hover:bg-gray-50 transition-colors w-full" href="{{ route('auth.google') }}">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5"> 
                        <span class="font-medium">Continue with Google</span>
                    </a>
                </div>

                <div class="my-6 flex items-center gap-4 text-xs text-gray-400">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    or
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <form class="space-y-4" method="POST" action="{{ route('register') }}">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Full Name</label>
                        <input class="input" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Enter your full name">
                        @error('name')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Business Email</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="Enter your email">
                        @error('email')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Password</label>
                            <input class="input" id="password" name="password" type="password" required placeholder="Create password">
                            @error('password')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Confirm Password</label>
                            <input class="input" id="password_confirmation" name="password_confirmation" type="password" required placeholder="Repeat password">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full rounded-xl py-3 font-medium">Create Account</button>
                </form>

                <p class="mt-6 text-sm text-gray-600">Already have an account? <a href="{{ route('login') }}" class="text-emerald-700 font-medium">Sign in</a></p>
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass rounded-3xl p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Meet MenetZero’s platform</span>
                    <ul class="mt-6 space-y-4 text-white/90">
                        <li class="flex gap-3"><span>✓</span> Understand your product’s emissions</li>
                        <li class="flex gap-3"><span>✓</span> Gain clarity on carbon hotspots</li>
                        <li class="flex gap-3"><span>✓</span> Test changes before production</li>
                        <li class="flex gap-3"><span>✓</span> Make confident low-carbon decisions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
