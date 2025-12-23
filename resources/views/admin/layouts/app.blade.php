<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin | MENetZero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #111827;
            background-color: #f3f4f6;
        }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-gray-100">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside class="w-72 bg-white border-r border-gray-200 flex flex-col">
            <div class="h-16 flex items-center px-4 border-b border-gray-200">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MENetZero" class="h-8 w-auto">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-900">MIDDLE EAST NET Zero</span>
                        <span class="text-xs text-purple-600 font-medium">Super Admin</span>
                    </div>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                @include('admin.partials.nav')
            </nav>

            <div class="border-t border-gray-200 p-4 text-xs text-gray-500">
                <div class="flex items-center justify-between">
                    <span>
                        @php $admin = auth('admin')->user(); @endphp
                        {{ $admin?->name ?? 'Admin' }}
                    </span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-700">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 flex flex-col">
            <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
                <h1 class="text-lg font-semibold text-gray-900">
                    @yield('page-title', 'Admin Dashboard')
                </h1>
            </header>

            <section class="flex-1 p-6">
                @yield('content')
            </section>
        </main>
    </div>

    @stack('scripts')
</body>
</html>


