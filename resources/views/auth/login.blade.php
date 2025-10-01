<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CarbonTracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        .text-eco-green { color: #28a745; }
        .bg-eco-green { background-color: #28a745; }
        .bg-eco-green-dark { background-color: #15803d; }
        .btn-primary { background-color: #28a745; color: #fff; }
        .btn-primary:hover { background-color: #15803d; }
        .input-field { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; }
        .label { display:block; font-size: 0.875rem; color: #374151; margin-bottom: 0.25rem; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 bg-eco-green rounded-full flex items-center justify-center">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.567 3-3.5 3S5 12.657 5 11s1.567-3 3.5-3S12 9.343 12 11zM19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">Sign in to your account</h2>
            <p class="mt-2 text-sm text-gray-600">Welcome back</p>
        </div>

        @if(session('status'))
            <div class="p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="p-3 rounded bg-red-100 text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="mt-8 space-y-6" method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="email" class="label">Email Address</label>
                    <input id="email" name="email" type="email" required class="input-field" value="{{ old('email') }}">
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" required class="input-field">
                </div>
            </div>
            <div>
                <button type="submit" class="btn-primary w-full py-2 px-4 rounded">Sign In</button>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Don't have an account? <a href="{{ route('register') }}" class="text-eco-green">Create one</a></p>
            </div>
        </form>
    </div>
</body>
</html>


