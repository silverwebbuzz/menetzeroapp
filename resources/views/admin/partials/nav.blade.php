@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp

<div class="px-3 space-y-6 text-sm">
    <div>
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2 mb-2">
            Overview
        </div>
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.dashboard') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/>
                </svg>
            </span>
            <span>Dashboard</span>
        </a>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2 mb-2">
            Management
        </div>
        <a href="{{ route('admin.companies.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.companies') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7h18M3 12h18M3 17h18"/>
                </svg>
            </span>
            <span>Companies</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.users') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5.121 17.804A4 4 0 018 17h8a4 4 0 012.879 1.116M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.subscription-plans') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.subscription-plans') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .843-3 1.882v4.236C9 15.157 10.343 16 12 16s3-.843 3-1.882V9.882C15 8.843 13.657 8 12 8z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 10a7 7 0 1114 0v4a7 7 0 11-14 0v-4z"/>
                </svg>
            </span>
            <span>Subscription Plans</span>
        </a>

        <a href="{{ route('admin.role-templates') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.role-templates') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6v6l4 2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5.121 17.804A4 4 0 018 17h8a4 4 0 012.879 1.116M4 6h16"/>
                </svg>
            </span>
            <span>Role Templates</span>
        </a>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2 mb-2">
            Analytics
        </div>
        <a href="{{ route('admin.statistics') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($routeName, 'admin.statistics') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 3v18M6 8v13M16 13v8M21 6v15"/>
                </svg>
            </span>
            <span>Statistics</span>
        </a>
    </div>

    <div>
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2 mb-2">
            Client Portal
        </div>
        <a href="{{ route('client.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50">
            <span class="inline-flex items-center justify-center w-5 h-5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"/>
                </svg>
            </span>
            <span>Back to Client Portal</span>
        </a>
    </div>
</div>


