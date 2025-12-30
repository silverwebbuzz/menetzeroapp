<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MIDDLE EAST NET Zero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Poppins', system-ui, -apple-system, sans-serif; 
            color: #111827 !important; 
            background-color: #f9fafb !important; 
        }
        .brand-gradient { 
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); 
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
            background-color: #7c3aed; 
            color: white; 
            border-color: #7c3aed; 
        }
        .btn-primary:hover { 
            background-color: #6d28d9; 
            border-color: #6d28d9; 
            transform: translateY(-1px); 
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
            border-color: #7c3aed; 
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15); 
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
        .brand-logo img {
            height: 2rem;
            width: auto;
        }
        .brand-logo-text { 
            color: #111827 !important; 
            font-weight: 600; 
            font-size: 1.125rem; 
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #ede9fe;
            color: #7c3aed;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="grid lg:grid-cols-2 min-h-screen">
        <div class="flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="mb-10">
                    <div class="brand-logo">
                        <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-8 w-auto">
                    </div>
                </div>
                
                <div class="admin-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>Admin Portal</span>
                </div>
                
                <h1 class="text-3xl font-semibold mb-2" style="color: #111827;">Admin Access</h1>
                <p class="text-sm mb-8" style="color: #4b5563;">Sign in to access the Super Admin panel.</p>

                <form class="space-y-6" method="POST" action="{{ route('admin.login.post') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input class="form-input" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your admin email..." autofocus>
                        @error('email')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label">Password</label>
                        </div>
                        <input class="form-input" type="password" name="password" required placeholder="Enter your password...">
                        @error('password')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm" style="color: #6b7280;">Remember me</span>
                        </label>
                    </div>
                    @if($errors->any())
                        <div class="p-4 rounded-lg" style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b;">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary btn-full">Login to Admin Panel</button>
                </form>

                <p class="mt-6 text-sm text-center" style="color: #6b7280;">
                    <a href="{{ route('login') }}" class="font-medium" style="color: #7c3aed;">← Back to Client/Partner Login</a>
                </p>
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass p-8 w-full max-w-2xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sm">Super Admin Control Panel</span>
                    <ul class="mt-6 space-y-4 text-white/90">
                        <li class="flex gap-3"><span>✓</span> Manage all companies and users</li>
                        <li class="flex gap-3"><span>✓</span> Configure subscription plans</li>
                        <li class="flex gap-3"><span>✓</span> Manage role templates</li>
                        <li class="flex gap-3"><span>✓</span> View system statistics</li>
                        <li class="flex gap-3"><span>✓</span> Monitor system-wide activity</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

