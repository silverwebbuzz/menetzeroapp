<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - MIDDLE EAST NET Zero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            color: #111827 !important; 
            background-color: #f9fafb !important; 
        }
        .brand-gradient { 
            background: linear-gradient(135deg, #0ea5a3 0%, #10b981 100%); 
        }
        .glass { 
            background: rgba(255, 255, 255, 0.06); 
            border: 1px solid rgba(255, 255, 255, 0.12); 
            backdrop-filter: blur(10px); 
            border-radius: 1.5rem; 
        }
        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem; 
            padding: 0.75rem 1.5rem; 
            border-radius: 1rem; 
            font-weight: 500; 
            text-decoration: none; 
            border: 1px solid transparent; 
            cursor: pointer; 
            transition: all 0.15s ease-in-out; 
        }
        .btn-primary { 
            background-color: #0ea5a3; 
            color: white; 
            border-color: #0ea5a3; 
        }
        .btn-primary:hover { 
            background-color: #0d9488; 
            border-color: #0d9488; 
            transform: translateY(-1px); 
        }
        .btn-ghost { 
            background-color: #f3f4f6; 
            color: #374151; 
            border-color: #e5e7eb; 
        }
        .btn-ghost:hover { 
            background-color: #e5e7eb; 
            color: #1f2937; 
        }
        .btn-full { 
            width: 100%; 
        }
        .form-group { 
            margin-bottom: 1.5rem; 
        }
        .form-label { 
            display: block; 
            font-size: 0.875rem; 
            font-weight: 500; 
            color: #374151 !important; 
            margin-bottom: 0.5rem; 
        }
        .form-input { 
            width: 100%; 
            padding: 0.75rem 1rem; 
            border: 1px solid #d1d5db; 
            border-radius: 1rem; 
            font-size: 1rem; 
            color: #111827 !important; 
            background-color: white !important; 
            transition: all 0.15s ease-in-out; 
        }
        .form-input:focus { 
            outline: none; 
            border-color: #0ea5a3; 
            box-shadow: 0 0 0 4px rgba(14, 165, 163, 0.15); 
        }
        .form-error { 
            color: #ef4444; 
            font-size: 0.875rem; 
            margin-top: 0.25rem; 
        }
        .brand-logo { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
        }
        .brand-logo-icon { 
            width: 2rem; 
            height: 2rem; 
            background-color: #0ea5a3; 
            border-radius: 0.5rem; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-weight: 700; 
            font-size: 0.875rem; 
        }
        .brand-logo-text { 
            color: #111827 !important; 
            font-weight: 600; 
            font-size: 1.125rem; 
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="grid lg:grid-cols-2 min-h-screen">
        <div class="flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="mb-10">
                    <div class="brand-logo">
                        <div class="brand-logo-icon">ME</div>
                        <div class="brand-logo-text">MIDDLE EAST NET Zero</div>
                    </div>
                </div>
                
                <h1 class="text-3xl font-semibold mb-2" style="color: #111827;">Sign up for MIDDLE EAST NET Zero</h1>
                <p class="text-sm mb-8" style="color: #4b5563;">Measure your organization's carbon footprint with ease.</p>

                <div class="mb-6">
                    <a class="btn btn-ghost btn-full" href="{{ route('auth.google') }}">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" class="w-5 h-5"> 
                        <span>Continue with Google</span>
                    </a>
                </div>

                <div class="flex items-center gap-4 text-xs mb-6" style="color: #9ca3af;">
                    <div class="h-px flex-1" style="background-color: #e5e7eb;"></div>
                    <span>or</span>
                    <div class="h-px flex-1" style="background-color: #e5e7eb;"></div>
                </div>

                <form class="space-y-6" method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input class="form-input" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Enter your full name">
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Business Email</label>
                        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="Enter your email">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input class="form-input" id="password" name="password" type="password" required placeholder="Create password">
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required placeholder="Repeat password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Create Account</button>
                </form>

                <p class="mt-6 text-sm text-center" style="color: #6b7280;">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-medium" style="color: #0ea5a3;">Sign in</a>
                </p>
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Meet MIDDLE EAST NET Zero's platform</span>
                    <ul class="mt-6 space-y-4 text-white/90">
                        <li class="flex gap-3"><span>✓</span> Understand your product's emissions</li>
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
