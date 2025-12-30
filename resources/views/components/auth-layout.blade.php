<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MIDDLE EAST NET Zero')</title>
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
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
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
    </style>
    @stack('head')
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
                
                @yield('content')
            </div>
        </div>

        <div class="relative brand-gradient text-white">
            <div class="relative h-full w-full flex items-center justify-center p-8">
                <div class="glass p-8 w-full max-w-2xl">
                    @yield('sidebar-content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>
