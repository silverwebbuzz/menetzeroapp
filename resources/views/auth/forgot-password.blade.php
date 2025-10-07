<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MenetZero</title>
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
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-teal-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">ME</span>
                        </div>
                        <div class="text-lg font-semibold text-gray-900">MIDDLE EAST NET Zero</div>
                    </div>
                </div>
                <h1 class="text-3xl font-semibold text-gray-900">Forgot Password?</h1>
                <p class="mt-2 text-sm text-gray-600">No worries, we'll send you reset instructions.</p>

                @if (session('status'))
                    <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        {{ session('status') }}
                    </div>
                @endif

                <form class="mt-8 space-y-4" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Business Email</label>
                        <input class="input" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email...">
                        @error('email')
                            <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full rounded-xl py-3 font-medium">Send Reset Link</button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-emerald-700 hover:underline">Back to Login</a>
                </div>
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass rounded-3xl p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Secure Password Reset</span>
                    <ul class="mt-6 space-y-4 text-white/90">
                        <li class="flex gap-3"><span>✓</span> Secure password reset process</li>
                        <li class="flex gap-3"><span>✓</span> Email verification required</li>
                        <li class="flex gap-3"><span>✓</span> Quick and easy recovery</li>
                        <li class="flex gap-3"><span>✓</span> Your account stays protected</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
