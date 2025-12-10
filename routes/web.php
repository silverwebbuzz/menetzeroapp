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
use App\Http\Controllers\AccountSelectorController;
use App\Http\Controllers\Partner\ExternalClientController;
use App\Http\Controllers\Partner\ExternalClientLocationController;
use App\Http\Controllers\Partner\ExternalClientMeasurementController;
use App\Http\Controllers\Partner\ExternalClientDocumentController;
use App\Http\Controllers\Partner\ExternalClientReportController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authentication routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials, true)) {
        $request->session()->regenerate();
        
        // Check if user has multiple company access
        $user = auth()->user();
        if ($user && $user->hasMultipleCompanyAccess()) {
            return redirect()->route('account.selector');
        }
        
        return redirect()->intended(route('client.dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
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
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Company setup routes (accessible after registration)
Route::middleware('auth')->group(function () {
    Route::get('/company/setup', [CompanySetupController::class, 'index'])->name('company.setup');
    Route::post('/company/setup', [CompanySetupController::class, 'store'])->name('company.setup.store');
});

// Partner Routes
Route::prefix('partner')->middleware(['auth', 'setActiveCompany', 'checkCompanyType:partner'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('partner.dashboard');
    
    // External Client Management
    Route::resource('clients', ExternalClientController::class);
    
    // External Client Locations
    Route::get('/clients/{client}/locations', [ExternalClientLocationController::class, 'index'])->name('partner.clients.locations.index');
    Route::post('/clients/{client}/locations', [ExternalClientLocationController::class, 'store'])->name('partner.clients.locations.store');
    Route::get('/clients/{client}/locations/{location}', [ExternalClientLocationController::class, 'show'])->name('partner.clients.locations.show');
    Route::put('/clients/{client}/locations/{location}', [ExternalClientLocationController::class, 'update'])->name('partner.clients.locations.update');
    Route::delete('/clients/{client}/locations/{location}', [ExternalClientLocationController::class, 'destroy'])->name('partner.clients.locations.destroy');
    
    // External Client Measurements
    Route::get('/clients/{client}/locations/{location}/measurements', [ExternalClientMeasurementController::class, 'index'])->name('partner.clients.measurements.index');
    Route::post('/clients/{client}/locations/{location}/measurements', [ExternalClientMeasurementController::class, 'store'])->name('partner.clients.measurements.store');
    Route::get('/clients/{client}/measurements/{measurement}', [ExternalClientMeasurementController::class, 'show'])->name('partner.clients.measurements.show');
    Route::put('/clients/{client}/measurements/{measurement}', [ExternalClientMeasurementController::class, 'update'])->name('partner.clients.measurements.update');
    Route::delete('/clients/{client}/measurements/{measurement}', [ExternalClientMeasurementController::class, 'destroy'])->name('partner.clients.measurements.destroy');
    
    // External Client Documents
    Route::get('/clients/{client}/documents', [ExternalClientDocumentController::class, 'index'])->name('partner.clients.documents.index');
    Route::post('/clients/{client}/documents', [ExternalClientDocumentController::class, 'store'])->name('partner.clients.documents.store');
    Route::get('/clients/{client}/documents/{document}', [ExternalClientDocumentController::class, 'show'])->name('partner.clients.documents.show');
    Route::delete('/clients/{client}/documents/{document}', [ExternalClientDocumentController::class, 'destroy'])->name('partner.clients.documents.destroy');
    
    // External Client Reports
    Route::get('/clients/{client}/reports', [ExternalClientReportController::class, 'index'])->name('partner.clients.reports.index');
    Route::post('/clients/{client}/reports', [ExternalClientReportController::class, 'generate'])->name('partner.clients.reports.generate');
    
    // Role Management routes
    Route::resource('roles', \App\Http\Controllers\Partner\RoleManagementController::class)->except(['show'])->names([
        'index' => 'partner.roles.index',
        'create' => 'partner.roles.create',
        'store' => 'partner.roles.store',
        'edit' => 'partner.roles.edit',
        'update' => 'partner.roles.update',
        'destroy' => 'partner.roles.destroy',
    ]);
    
    // Staff Management routes - EXPLICIT NAMES to avoid conflicts
    Route::prefix('staff')->name('partner.staff.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Partner\StaffManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Partner\StaffManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Partner\StaffManagementController::class, 'store'])->name('store');
        Route::put('/{access}/role', [\App\Http\Controllers\Partner\StaffManagementController::class, 'updateRole'])->name('update-role');
        Route::delete('/{access}', [\App\Http\Controllers\Partner\StaffManagementController::class, 'destroy'])->name('destroy');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\Partner\StaffManagementController::class, 'cancelInvitation'])->name('cancel-invitation');
    });
    
    // Subscription & Billing routes
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

// Account Switcher (for multi-company staff)
Route::middleware(['auth'])->group(function () {
    Route::get('/account/selector', [AccountSelectorController::class, 'index'])->name('account.selector');
    Route::post('/account/switch', [AccountSelectorController::class, 'switch'])->name('account.switch');
});

// Client Routes
Route::middleware(['auth', 'setActiveCompany', 'checkCompanyType:client'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('client.dashboard');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('client.profile');
    Route::post('/profile/personal', [ProfileController::class, 'updatePersonal'])->name('profile.update.personal');
    Route::post('/profile/company', [ProfileController::class, 'updateCompany'])->name('profile.update.company');
    
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
    
    // Staff Management routes - EXPLICIT NAMES for client
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StaffManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\StaffManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\StaffManagementController::class, 'store'])->name('store');
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
    });
    
    // OLD EMISSION FORM ROUTES - REPLACED BY MEASUREMENTS SYSTEM
    // Route::prefix('emission-form')->name('emission-form.')->group(function () {
    //     Route::get('/', [EmissionFormController::class, 'index'])->name('index');
    //     Route::get('/step/{step}', [EmissionFormController::class, 'showStep'])->name('step');
    //     Route::post('/step/{step}', [EmissionFormController::class, 'storeStep'])->name('store');
    //     Route::get('/review', [EmissionFormController::class, 'review'])->name('review');
    //     Route::post('/submit', [EmissionFormController::class, 'submit'])->name('submit');
    //     Route::get('/success', [EmissionFormController::class, 'success'])->name('success');
    //     Route::post('/ocr', [EmissionFormController::class, 'extractOCRData'])->name('ocr');
    // });
    
    // OLD EMISSION MANAGEMENT ROUTES - REPLACED BY MEASUREMENTS SYSTEM
    // Route::prefix('emissions')->name('emissions.')->group(function () {
    //     Route::get('/management', [EmissionManagementController::class, 'index'])->name('management');
    //     Route::get('/{emission}', [EmissionManagementController::class, 'show'])->name('show');
    //     Route::get('/{emission}/edit', [EmissionManagementController::class, 'edit'])->name('edit');
    //     Route::post('/{emission}/duplicate', [EmissionManagementController::class, 'duplicate'])->name('duplicate');
    //     Route::delete('/{emission}/delete', [EmissionManagementController::class, 'delete'])->name('delete');
    //     Route::patch('/{emission}/status', [EmissionManagementController::class, 'updateStatus'])->name('update-status');
    //     Route::post('/{emission}/recalculate', [EmissionManagementController::class, 'recalculate'])->name('recalculate');
    //     Route::get('/{emission}/breakdown', [EmissionManagementController::class, 'getBreakdown'])->name('breakdown');
    // });
    
    // Admin Authentication Routes (Public)
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
});
