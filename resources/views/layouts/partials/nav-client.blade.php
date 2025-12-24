{{-- Client Navigation Menu --}}
@php
    // Always use the web guard here so admin users (App\Models\Admin) don't hit client-specific methods
    $user = auth('web')->user();

    // Check for active company (works for both owners and staff members), only if method exists
    $activeCompany = ($user && method_exists($user, 'getActiveCompany'))
        ? $user->getActiveCompany()
        : null;
    $hasCompany = $activeCompany !== null;
    $companyId = $activeCompany ? $activeCompany->id : null;
    
    // Check permissions for each module
    // Super admin and company admin have all permissions
    $isAdmin = $user && ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId)));
    
    // Locations permissions: locations.*, manage_locations, or module 'locations' with action 'view'
    $canViewLocations = $isAdmin || ($hasCompany && (
        $user->hasPermission('locations.*', $companyId) ||
        $user->hasPermission('manage_locations', $companyId) ||
        $user->hasModulePermission('locations', 'view', $companyId)
    ));
    
    // Measurements permissions: measurements.view, measurements.*, manage_measurements, or module 'measurements' with action 'view'
    $canViewMeasurements = $isAdmin || ($hasCompany && (
        $user->hasPermission('measurements.view', $companyId) ||
        $user->hasPermission('measurements.*', $companyId) ||
        $user->hasPermission('manage_measurements', $companyId) ||
        $user->hasModulePermission('measurements', 'view', $companyId)
    ));
    
    // Documents permissions: documents.upload, upload_documents, or module 'documents' with action 'view' or 'upload'
    $canViewDocuments = $isAdmin || ($hasCompany && (
        $user->hasPermission('documents.upload', $companyId) ||
        $user->hasPermission('upload_documents', $companyId) ||
        $user->hasModulePermission('documents', 'view', $companyId) ||
        $user->hasModulePermission('documents', 'upload', $companyId)
    ));
    
    // Reports permissions: reports.view, reports.*, or module 'reports' with action 'view'
    $canViewReports = $isAdmin || ($hasCompany && (
        $user->hasPermission('reports.view', $companyId) ||
        $user->hasPermission('reports.*', $companyId) ||
        $user->hasModulePermission('reports', 'view', $companyId)
    ));
    
    // Staff Management permissions: staff_management module with view action
    $canViewStaff = $isAdmin || ($hasCompany && $user->hasModulePermission('staff_management', 'view', $companyId));
    
    // Roles permissions: roles_permissions module with view action
    $canViewRoles = $isAdmin || ($hasCompany && $user->hasModulePermission('roles_permissions', 'view', $companyId));
@endphp

<div class="flex flex-col h-full">
<div class="flex-1">

<a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
    </svg>
    Dashboard
</a>

@if($hasCompany)
@if($canViewLocations)
<a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
    </svg>
    Locations
</a>
@endif

@if($canViewMeasurements)
<a href="{{ route('measurements.index') }}" class="nav-link {{ request()->routeIs('measurements.*') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
    Measurements
</a>
@endif

@if($hasCompany)
<!-- Quick Input Section -->
<div class="mt-6 mb-2">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">
        Quick Input
    </div>
</div>

@if($canViewMeasurements)
<a href="{{ route('quick-input.index') }}" class="nav-link {{ request()->routeIs('quick-input.index') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    View Entries
</a>
@endif

<div x-data="{ 
    scope1Open: false,
    scope2Open: false,
    scope3Open: false
}" class="space-y-1">
    <!-- Scope 1 -->
    <div>
        <button @click="scope1Open = !scope1Open" class="nav-link w-full text-left flex items-center justify-between">
            <div class="flex items-center">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Scope 1</span>
            </div>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': scope1Open }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="scope1Open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1"
             class="ml-4 space-y-1">
            <a href="#" class="nav-link text-sm py-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                </svg>
                Natural Gas
            </a>
            <a href="#" class="nav-link text-sm py-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Fuel
            </a>
            <a href="#" class="nav-link text-sm py-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Vehicle
            </a>
            <a href="#" class="nav-link text-sm py-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Refrigerants
            </a>
            <a href="#" class="nav-link text-sm py-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Process
            </a>
        </div>
    </div>

    <!-- Scope 2 -->
    <div>
        <button @click="scope2Open = !scope2Open" class="nav-link w-full text-left flex items-center justify-between">
            <div class="flex items-center">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Scope 2</span>
            </div>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': scope2Open }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="scope2Open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1"
             class="ml-4 space-y-1">
            <a href="{{ route('quick-input.show', ['scope' => 2, 'slug' => 'electricity']) }}" class="nav-link text-sm py-2 {{ request()->routeIs('quick-input.show') && request()->route('slug') == 'electricity' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Electricity
            </a>
            <a href="{{ route('quick-input.show', ['scope' => 2, 'slug' => 'heat-steam-cooling']) }}" class="nav-link text-sm py-2 {{ request()->routeIs('quick-input.show') && request()->route('slug') == 'heat-steam-cooling' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Heat, Steam & Cooling
            </a>
        </div>
    </div>

    <!-- Scope 3 -->
    <div>
        <button @click="scope3Open = !scope3Open" class="nav-link w-full text-left flex items-center justify-between">
            <div class="flex items-center">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Scope 3</span>
            </div>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': scope3Open }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="scope3Open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1"
             class="ml-4 space-y-1">
            <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'flights']) }}" class="nav-link text-sm py-2 {{ request()->routeIs('quick-input.show') && request()->route('slug') == 'flights' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                Flights
            </a>
            <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'public-transport']) }}" class="nav-link text-sm py-2 {{ request()->routeIs('quick-input.show') && request()->route('slug') == 'public-transport' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Public Transport
            </a>
            <a href="{{ route('quick-input.show', ['scope' => 3, 'slug' => 'home-workers']) }}" class="nav-link text-sm py-2 {{ request()->routeIs('quick-input.show') && request()->route('slug') == 'home-workers' ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Home Workers
            </a>
        </div>
    </div>
</div>
@endif

@if($canViewDocuments)
<!-- AI Smart Uploads Section -->
<div class="mt-6 mb-2">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">
        AI Smart Uploads
    </div>
</div>

<a href="{{ route('document-uploads.index') }}" class="nav-link {{ request()->routeIs('document-uploads.*') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
    </svg>
    Upload Bills
</a>

<a href="{{ route('document-uploads.index', ['status' => 'extracted']) }}" class="nav-link">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
    </svg>
    View Extracted Data
</a>

<a href="{{ route('document-uploads.index', ['status' => 'approved']) }}" class="nav-link">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    Approved Items
</a>
@endif

@if($canViewReports)
<!-- Reports and Profile Section -->
<div class="mt-6 mb-2">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">
        Reports & Profile
    </div>
</div>

<a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    Reports
</a>
@endif
@endif

<a href="{{ route('client.profile') }}" class="nav-link {{ request()->routeIs('client.profile') || request()->routeIs('profile.*') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
    </svg>
    My Profile
</a>

@if($user && method_exists($user, 'isAdmin') && $user->isAdmin())
<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
    </svg>
    Admin
</a>
@endif

</div>

<!-- Settings Section -->
@if($hasCompany && ($canViewStaff || $canViewRoles || $isAdmin))
<div class="mt-auto pt-6 border-t border-gray-200">
    <div class="mt-6 mb-2">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">
            Settings
        </div>
    </div>
    
    @if($canViewStaff || $canViewRoles)
    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') || request()->routeIs('staff.*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        Staff Management
    </a>
    @endif
    
    @if($isAdmin)
    <a href="{{ route('subscriptions.billing') }}" class="nav-link {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        Billing
    </a>
    @endif
</div>
@endif
</div>

