<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EmissionBoundaryController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\DocumentUploadController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authentication routes - Client
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', function () {
    return view('auth.login', ['isPartner' => false]);
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Use 'web' guard (users table - clients and staff)
    if (\Illuminate\Support\Facades\Auth::guard('web')->attempt($credentials, true)) {
        $request->session()->regenerate();
        
        $user = auth('web')->user();
        
        // Check if user has multiple company access (owned + staff)
        if ($user && $user->hasMultipleCompanyAccess()) {
            return redirect()->route('account.selector');
        }
        
        // Single company or no company - go to dashboard
        return redirect()->intended(route('client.dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

// Authentication routes - Partner
Route::get('/partner/register', [RegisterController::class, 'showRegistrationForm'])->name('partner.register');
Route::post('/partner/register', [RegisterController::class, 'register']);

Route::get('/partner/login', function () {
    return view('auth.login', ['isPartner' => true]);
})->name('partner.login');

Route::post('/partner/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Use 'partner' guard (users_partner table - partners only)
    if (\Illuminate\Support\Facades\Auth::guard('partner')->attempt($credentials, true)) {
        $request->session()->regenerate();
        return redirect()->intended(route('partner.dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('partner.login.post');

Route::post('/logout', function () {
    // Logout from web guard
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// OAuth routes
Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Password reset routes
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset-success', [ForgotPasswordController::class, 'resetSuccess'])->name('password.reset-success');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Invitation acceptance route (public)
Route::get('/invitations/accept/{token}', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/invitations/accept/{token}', [\App\Http\Controllers\InvitationController::class, 'processAccept'])->name('invitations.accept.process');
Route::get('/invitations/{token}/setup-password/{password_token}', [\App\Http\Controllers\InvitationController::class, 'setupPassword'])->name('invitations.setup-password');
Route::post('/invitations/{token}/setup-password/{password_token}', [\App\Http\Controllers\InvitationController::class, 'processSetupPassword'])->name('invitations.setup-password.process');

// Company setup route (POST only - form is on dashboard, not separate page)
Route::middleware('auth')->group(function () {
    Route::post('/company/setup', [CompanySetupController::class, 'store'])->name('company.setup.store');
});

// Account Switcher (for users with multiple company access - owned + staff)
Route::middleware(['auth:web'])->group(function () {
    Route::get('/account/selector', [\App\Http\Controllers\AccountSelectorController::class, 'index'])->name('account.selector');
    Route::post('/account/switch', [\App\Http\Controllers\AccountSelectorController::class, 'switch'])->name('account.switch');
});

// Profile Routes - Separate for Client and Partner

// Client Routes - Use web guard (default)
Route::middleware(['auth:web', 'setActiveCompany', 'checkCompanyType:client'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('client.dashboard');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('client.profile');
    Route::post('/profile/personal', [ProfileController::class, 'updatePersonal'])->name('profile.update.personal');
    Route::post('/profile/company', [ProfileController::class, 'updateCompany'])->name('profile.update.company');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
    
    // Location routes
    Route::resource('locations', LocationController::class);
    Route::post('/locations/{location}/toggle-status', [LocationController::class, 'toggleStatus'])->name('locations.toggle-status');
    Route::post('/locations/{location}/toggle-head-office', [LocationController::class, 'toggleHeadOffice'])->name('locations.toggle-head-office');
    Route::post('/locations/step/{step}', [LocationController::class, 'storeStep'])->name('locations.store-step');
    
    // Emission boundaries routes
    Route::get('/locations/{location}/emission-boundaries', [EmissionBoundaryController::class, 'index'])->name('emission-boundaries.index');
    Route::post('/locations/{location}/emission-boundaries', [EmissionBoundaryController::class, 'store'])->name('emission-boundaries.store');
    
    // Measurements routes
    Route::resource('measurements', MeasurementController::class)->except(['create', 'store', 'edit']);
    Route::post('/measurements/{measurement}/submit', [MeasurementController::class, 'submit'])->name('measurements.submit');
    
    // Emission source calculation routes
    Route::get('/measurements/{measurement}/sources/{source}/calculate', [MeasurementController::class, 'calculateSource'])->name('measurements.calculate-source');
    Route::post('/measurements/{measurement}/sources/{source}/calculate', [MeasurementController::class, 'storeSourceData'])->name('measurements.store-source-data');
    Route::get('/measurements/{measurement}/sources/{source}/edit', [MeasurementController::class, 'editSource'])->name('measurements.edit-source');
    Route::put('/measurements/{measurement}/sources/{source}/edit', [MeasurementController::class, 'updateSourceData'])->name('measurements.update-source-data');
    Route::delete('/measurements/{measurement}/sources/{source}', [MeasurementController::class, 'deleteSourceData'])->name('measurements.delete-source-data');
    
    // Document Upload routes (AI Smart Uploads)
    Route::prefix('document-uploads')->name('document-uploads.')->group(function () {
        Route::get('/', [DocumentUploadController::class, 'index'])->name('index');
        Route::get('/create', [DocumentUploadController::class, 'create'])->name('create');
        Route::post('/', [DocumentUploadController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentUploadController::class, 'show'])->name('show');
        Route::get('/{document}/edit', [DocumentUploadController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentUploadController::class, 'update'])->name('update');
        Route::post('/{document}/approve', [DocumentUploadController::class, 'approve'])->name('approve');
        Route::post('/{document}/reject', [DocumentUploadController::class, 'reject'])->name('reject');
        Route::delete('/{document}', [DocumentUploadController::class, 'destroy'])->name('destroy');
        Route::post('/{document}/retry-ocr', [DocumentUploadController::class, 'retryOcr'])->name('retry-ocr');
        Route::get('/{document}/field-mapping', [DocumentUploadController::class, 'showFieldMapping'])->name('field-mapping');
        Route::put('/{document}/field-mapping', [DocumentUploadController::class, 'updateFieldMapping'])->name('update-field-mapping');
        Route::get('/{document}/assign-scope', [DocumentUploadController::class, 'showScopeAssignment'])->name('assign-scope');
        Route::put('/{document}/assign-scope', [DocumentUploadController::class, 'updateScopeAssignment'])->name('update-assign-scope');
    });
    
    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('reports.index');
        })->name('index');
    });
    
    // Role Management routes - EXPLICIT NAMES for client
    Route::resource('roles', \App\Http\Controllers\RoleManagementController::class)->except(['show'])->names([
        'index' => 'roles.index',
        'create' => 'roles.create',
        'store' => 'roles.store',
        'edit' => 'roles.edit',
        'update' => 'roles.update',
        'destroy' => 'roles.destroy',
    ]);
    
    // Staff Management routes - Redirect to roles page (combined view)
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', function() { return redirect()->route('roles.index'); })->name('index');
        Route::get('/create', function() { return redirect()->route('roles.index'); })->name('create');
        Route::post('/', [\App\Http\Controllers\StaffManagementController::class, 'store'])->name('store');
        Route::get('/invitations/{invitation}/success', [\App\Http\Controllers\StaffManagementController::class, 'invitationSuccess'])->name('invitation-success');
        Route::put('/{access}/role', [\App\Http\Controllers\StaffManagementController::class, 'updateRole'])->name('update-role');
        Route::delete('/{access}', [\App\Http\Controllers\StaffManagementController::class, 'destroy'])->name('destroy');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\StaffManagementController::class, 'cancelInvitation'])->name('cancel-invitation');
    });
    
    // Subscription & Billing routes
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\SubscriptionController::class, 'index'])->name('index');
        Route::get('/current-plan', [\App\Http\Controllers\Client\SubscriptionController::class, 'currentPlan'])->name('current-plan');
        Route::get('/upgrade', [\App\Http\Controllers\Client\SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [\App\Http\Controllers\Client\SubscriptionController::class, 'processUpgrade'])->name('process-upgrade');
        Route::get('/billing', [\App\Http\Controllers\Client\SubscriptionController::class, 'billing'])->name('billing');
        Route::get('/payment-history', [\App\Http\Controllers\Client\SubscriptionController::class, 'paymentHistory'])->name('payment-history');
        Route::post('/cancel', [\App\Http\Controllers\Client\SubscriptionController::class, 'cancel'])->name('cancel');
        
        // Billing Methods routes
        Route::post('/billing-methods', [\App\Http\Controllers\Client\SubscriptionController::class, 'storeBillingMethod'])->name('billing-methods.store');
        Route::put('/billing-methods/{billingMethod}', [\App\Http\Controllers\Client\SubscriptionController::class, 'updateBillingMethod'])->name('billing-methods.update');
        Route::delete('/billing-methods/{billingMethod}', [\App\Http\Controllers\Client\SubscriptionController::class, 'destroyBillingMethod'])->name('billing-methods.destroy');
        Route::post('/billing-methods/{billingMethod}/set-default', [\App\Http\Controllers\Client\SubscriptionController::class, 'setDefaultBillingMethod'])->name('billing-methods.set-default');
    });
    
});

// Partner Routes - Use partner guard
Route::prefix('partner')->middleware(['auth:partner', 'setActiveCompany', 'checkCompanyType:partner'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('partner.dashboard');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('partner.profile');
    Route::post('/profile/personal', [ProfileController::class, 'updatePersonal'])->name('partner.profile.update.personal');
    Route::post('/profile/company', [ProfileController::class, 'updateCompany'])->name('partner.profile.update.company');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('partner.profile.update.password');
    
    // External Client Management
    Route::resource('clients', \App\Http\Controllers\Partner\ExternalClientController::class)->names([
        'index' => 'partner.clients.index',
        'create' => 'partner.clients.create',
        'store' => 'partner.clients.store',
        'show' => 'partner.clients.show',
        'edit' => 'partner.clients.edit',
        'update' => 'partner.clients.update',
        'destroy' => 'partner.clients.destroy',
    ]);
    
    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('reports.index');
        })->name('index');
    });
    
    // Role Management routes - EXPLICIT NAMES for partner
    Route::resource('roles', \App\Http\Controllers\RoleManagementController::class)->except(['show'])->names([
        'index' => 'partner.roles.index',
        'create' => 'partner.roles.create',
        'store' => 'partner.roles.store',
        'edit' => 'partner.roles.edit',
        'update' => 'partner.roles.update',
        'destroy' => 'partner.roles.destroy',
    ]);
    
    // Staff Management routes - Redirect to roles page (combined view)
    Route::prefix('staff')->name('partner.staff.')->group(function () {
        Route::get('/', function() { return redirect()->route('partner.roles.index'); })->name('index');
        Route::get('/create', function() { return redirect()->route('partner.roles.index'); })->name('create');
        Route::post('/', [\App\Http\Controllers\StaffManagementController::class, 'store'])->name('store');
        Route::get('/invitations/{invitation}/success', [\App\Http\Controllers\StaffManagementController::class, 'invitationSuccess'])->name('invitation-success');
        Route::put('/{access}/role', [\App\Http\Controllers\StaffManagementController::class, 'updateRole'])->name('update-role');
        Route::delete('/{access}', [\App\Http\Controllers\StaffManagementController::class, 'destroy'])->name('destroy');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\StaffManagementController::class, 'cancelInvitation'])->name('cancel-invitation');
    });
    
    // Subscription & Billing routes for partner
    Route::prefix('subscriptions')->name('partner.subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Partner\SubscriptionController::class, 'index'])->name('index');
        Route::get('/current-plan', [\App\Http\Controllers\Partner\SubscriptionController::class, 'currentPlan'])->name('current-plan');
        Route::get('/upgrade', [\App\Http\Controllers\Partner\SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [\App\Http\Controllers\Partner\SubscriptionController::class, 'processUpgrade'])->name('process-upgrade');
        Route::get('/billing', [\App\Http\Controllers\Partner\SubscriptionController::class, 'billing'])->name('billing');
        Route::get('/payment-history', [\App\Http\Controllers\Partner\SubscriptionController::class, 'paymentHistory'])->name('payment-history');
        Route::post('/cancel', [\App\Http\Controllers\Partner\SubscriptionController::class, 'cancel'])->name('cancel');
    });
});

// Admin Authentication Routes (Public - outside middleware)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\AdminLoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminLoginController::class, 'logout'])->name('logout');
});

// Super Admin routes (Protected)
Route::prefix('admin')->name('admin.')->middleware(['ensureSuperAdmin'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\SuperAdminController::class, 'dashboard'])->name('dashboard');
        
        // Companies Management
        Route::get('/companies', [\App\Http\Controllers\Admin\SuperAdminController::class, 'companies'])->name('companies.index');
        Route::get('/companies/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'showCompany'])->name('companies.show');
        
        // Subscription Plans Management
        Route::get('/subscription-plans', [\App\Http\Controllers\Admin\SuperAdminController::class, 'subscriptionPlans'])->name('subscription-plans');
        Route::get('/subscription-plans/create', [\App\Http\Controllers\Admin\SuperAdminController::class, 'createSubscriptionPlan'])->name('subscription-plans.create');
        Route::post('/subscription-plans', [\App\Http\Controllers\Admin\SuperAdminController::class, 'storeSubscriptionPlan'])->name('subscription-plans.store');
        Route::get('/subscription-plans/{id}/edit', [\App\Http\Controllers\Admin\SuperAdminController::class, 'editSubscriptionPlan'])->name('subscription-plans.edit');
        Route::put('/subscription-plans/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'updateSubscriptionPlan'])->name('subscription-plans.update');
        
        // Role Templates Management
        Route::get('/role-templates', [\App\Http\Controllers\Admin\SuperAdminController::class, 'roleTemplates'])->name('role-templates');
        Route::get('/role-templates/create', [\App\Http\Controllers\Admin\SuperAdminController::class, 'createRoleTemplate'])->name('role-templates.create');
        Route::post('/role-templates', [\App\Http\Controllers\Admin\SuperAdminController::class, 'storeRoleTemplate'])->name('role-templates.store');
        Route::get('/role-templates/{id}/edit', [\App\Http\Controllers\Admin\SuperAdminController::class, 'editRoleTemplate'])->name('role-templates.edit');
        Route::put('/role-templates/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'updateRoleTemplate'])->name('role-templates.update');
        
        // Users Management
        Route::get('/users', [\App\Http\Controllers\Admin\SuperAdminController::class, 'users'])->name('users.index');
        Route::get('/users/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'showUser'])->name('users.show');
        
        // Statistics
        Route::get('/statistics', [\App\Http\Controllers\Admin\SuperAdminController::class, 'statistics'])->name('statistics');
    });
