<?php

use App\Http\Controllers\ReportController;
use App\Models\Report;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EmissionBoundaryController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public marketing + policy pages (required for payment gateway whitelisting)
Route::get('/pricing', [\App\Http\Controllers\PageController::class, 'pricing'])->name('pricing');
Route::get('/contact', [\App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::get('/terms', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'terms')->name('terms');
Route::get('/refunds', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'refunds')->name('refunds');
Route::get('/privacy', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');
Route::get('/currency/{code}', [\App\Http\Controllers\PageController::class, 'switchCurrency'])->name('currency.switch');

// Consultant partner portal (separate auth guard)
Route::prefix('consultant')->name('consultant.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Consultant\AuthController::class, 'landing'])->name('landing');
    Route::get('/register', [\App\Http\Controllers\Consultant\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Consultant\AuthController::class, 'register'])->name('register.post');
    Route::get('/login', [\App\Http\Controllers\Consultant\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Consultant\AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [\App\Http\Controllers\Consultant\AuthController::class, 'logout'])->name('logout');

    Route::middleware(['ensureConsultant'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Consultant\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Consultant\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Consultant\ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/submit', [\App\Http\Controllers\Consultant\ProfileController::class, 'submitForReview'])->name('profile.submit');
        Route::get('/documents', [\App\Http\Controllers\Consultant\DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [\App\Http\Controllers\Consultant\DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [\App\Http\Controllers\Consultant\DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/intro-requests', [\App\Http\Controllers\Consultant\IntroRequestController::class, 'index'])->name('intro-requests.index');
        Route::get('/orders', [\App\Http\Controllers\Consultant\OrderController::class, 'index'])->name('orders.index');
    });
});

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
        
        $company = $user->getActiveCompany();
        if ($company?->isPartner()) {
            return redirect()->intended(route('partner.dashboard'));
        }

        return redirect()->intended(route('client.dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

// Authentication routes - Partner (DISABLED: partner guard not configured in config/auth.php
// and Partner\* controllers do not exist. Re-enable after wiring the partner guard and controllers.)
/*
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
*/

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
Route::middleware([
    'auth:web',
    'setActiveCompany',
    'ensurePartnerManagedWorkspace',
    'checkCompanyType:client',
    'restrictManagedClientWorkspace',
    'ensureOnboardingComplete',
])->group(function () {
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
    
    // Legacy measurements UI removed — redirect old URLs to Quick Input
    Route::redirect('/measurements', '/quick-input/entries');
    Route::get('/measurements/{path}', fn () => redirect()->route('quick-input.index'))->where('path', '.*');

    // Quick Input routes
    Route::prefix('quick-input')->name('quick-input.')->group(function () {
        Route::get('/entries', [\App\Http\Controllers\QuickInputController::class, 'index'])->name('index');
        Route::get('/help-guide', [\App\Http\Controllers\QuickInputController::class, 'helpGuide'])->name('help-guide');
        Route::get('/entries/export', [\App\Http\Controllers\QuickInputController::class, 'export'])->name('export');
        Route::get('/bulk-import/template', [\App\Http\Controllers\Scope12BulkImportController::class, 'downloadTemplate'])->name('bulk-import.template');
        Route::post('/bulk-import', [\App\Http\Controllers\Scope12BulkImportController::class, 'import'])->name('bulk-import.import');
        Route::get('/entries/{id}', [\App\Http\Controllers\QuickInputController::class, 'view'])->name('view');
        Route::get('/entries/{id}/edit', [\App\Http\Controllers\QuickInputController::class, 'edit'])->name('edit');
        Route::put('/entries/{id}', [\App\Http\Controllers\QuickInputController::class, 'update'])->name('update');
        Route::delete('/entries/{id}', [\App\Http\Controllers\QuickInputController::class, 'destroy'])->name('destroy');
        Route::get('/{scope}/{slug}', [\App\Http\Controllers\QuickInputController::class, 'show'])->name('show');
        Route::post('/{scope}/{slug}', [\App\Http\Controllers\QuickInputController::class, 'store'])->name('store');
    });
    
    // Quick Input API routes (AJAX)
    Route::prefix('api/quick-input')->name('api.quick-input.')->group(function () {
        Route::post('/calculate', [\App\Http\Controllers\QuickInputController::class, 'calculate'])->name('calculate');
        Route::get('/fuel-categories/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getFuelCategories'])->name('fuel-categories');
        Route::get('/fuel-types/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getFuelTypes'])->name('fuel-types');
        Route::get('/units/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getUnits'])->name('units');
        Route::get('/vehicle-types/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getVehicleTypes'])->name('vehicle-types');
        Route::get('/vehicle-fuel-types/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getVehicleFuelTypes'])->name('vehicle-fuel-types');
        Route::get('/vehicle-uoms/{sourceId}', [\App\Http\Controllers\QuickInputController::class, 'getVehicleUoms'])->name('vehicle-uoms');
        Route::get('/get-vehicle-form-fields', [\App\Http\Controllers\QuickInputController::class, 'getFormFieldsForVehicle'])->name('vehicle-form-fields');
    });
    
    // Company reporting methodology settings (Phase 0)
    Route::get('/settings/reporting', [\App\Http\Controllers\CompanyReportingSettingsController::class, 'edit'])->name('settings.reporting');
    Route::post('/settings/reporting', [\App\Http\Controllers\CompanyReportingSettingsController::class, 'update'])->name('settings.reporting.update');

    // IFRS S1 / S2 disclosures (Phase 1–2)
    Route::prefix('disclosures')->name('disclosures.')->middleware('disclosureAccess')->group(function () {
        Route::get('/', [\App\Http\Controllers\Disclosure\OverviewController::class, 'hub'])->name('hub');
        Route::redirect('/overview', '/disclosures');

        // IFRS S2 (Phase 1)
        Route::prefix('ifrs-s2')->name('s2.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Disclosure\OverviewController::class, 's2'])->name('overview');
            Route::get('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'editS2'])->name('sections.edit');
            Route::post('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'updateS2'])->name('sections.update');

            Route::get('/climate-risks', [\App\Http\Controllers\Disclosure\ClimateRiskController::class, 'index'])->name('climate-risks.index');
            Route::post('/climate-risks', [\App\Http\Controllers\Disclosure\ClimateRiskController::class, 'store'])->name('climate-risks.store');
            Route::put('/climate-risks/{climateRisk}', [\App\Http\Controllers\Disclosure\ClimateRiskController::class, 'update'])->name('climate-risks.update');
            Route::delete('/climate-risks/{climateRisk}', [\App\Http\Controllers\Disclosure\ClimateRiskController::class, 'destroy'])->name('climate-risks.destroy');

            Route::get('/opportunities', [\App\Http\Controllers\Disclosure\ClimateOpportunityController::class, 'index'])->name('climate-opportunities.index');
            Route::post('/opportunities', [\App\Http\Controllers\Disclosure\ClimateOpportunityController::class, 'store'])->name('climate-opportunities.store');
            Route::put('/opportunities/{climateOpportunity}', [\App\Http\Controllers\Disclosure\ClimateOpportunityController::class, 'update'])->name('climate-opportunities.update');
            Route::delete('/opportunities/{climateOpportunity}', [\App\Http\Controllers\Disclosure\ClimateOpportunityController::class, 'destroy'])->name('climate-opportunities.destroy');

            Route::get('/targets', [\App\Http\Controllers\Disclosure\ReductionTargetController::class, 'index'])->name('targets.index');
            Route::post('/targets', [\App\Http\Controllers\Disclosure\ReductionTargetController::class, 'store'])->name('targets.store');
            Route::put('/targets/{reductionTarget}', [\App\Http\Controllers\Disclosure\ReductionTargetController::class, 'update'])->name('targets.update');
            Route::delete('/targets/{reductionTarget}', [\App\Http\Controllers\Disclosure\ReductionTargetController::class, 'destroy'])->name('targets.destroy');

            Route::get('/report', [\App\Http\Controllers\Disclosure\IfrsS2ReportController::class, 'preview'])->name('report.preview');
            Route::get('/report/pdf', [\App\Http\Controllers\Disclosure\IfrsS2ReportController::class, 'exportPdf'])->name('report.pdf');
        });

        // IFRS S1 (Phase 2)
        Route::prefix('ifrs-s1')->name('s1.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Disclosure\OverviewController::class, 's1'])->name('overview');
            Route::get('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'editS1'])->name('sections.edit');
            Route::post('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'updateS1'])->name('sections.update');

            Route::get('/material-topics', [\App\Http\Controllers\Disclosure\MaterialTopicsController::class, 'edit'])->name('material-topics');
            Route::post('/material-topics', [\App\Http\Controllers\Disclosure\MaterialTopicsController::class, 'update'])->name('material-topics.update');

            Route::get('/sustainability-risks', [\App\Http\Controllers\Disclosure\SustainabilityRiskController::class, 'index'])->name('sustainability-risks.index');
            Route::post('/sustainability-risks', [\App\Http\Controllers\Disclosure\SustainabilityRiskController::class, 'store'])->name('sustainability-risks.store');
            Route::put('/sustainability-risks/{sustainabilityRisk}', [\App\Http\Controllers\Disclosure\SustainabilityRiskController::class, 'update'])->name('sustainability-risks.update');
            Route::delete('/sustainability-risks/{sustainabilityRisk}', [\App\Http\Controllers\Disclosure\SustainabilityRiskController::class, 'destroy'])->name('sustainability-risks.destroy');

            Route::get('/report', [\App\Http\Controllers\Disclosure\IfrsS1ReportController::class, 'preview'])->name('report.preview');
            Route::get('/report/pdf', [\App\Http\Controllers\Disclosure\IfrsS1ReportController::class, 'exportPdf'])->name('report.pdf');
        });

        // GRI (Phase 3)
        Route::prefix('gri')->name('gri.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Disclosure\OverviewController::class, 'gri'])->name('overview');
            Route::get('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'editGri'])->name('sections.edit');
            Route::post('/sections/{section}', [\App\Http\Controllers\Disclosure\SectionController::class, 'updateGri'])->name('sections.update');

            Route::get('/material-topics', [\App\Http\Controllers\Disclosure\MaterialTopicsController::class, 'editGri'])->name('material-topics');
            Route::post('/material-topics', [\App\Http\Controllers\Disclosure\MaterialTopicsController::class, 'updateGri'])->name('material-topics.update');

            Route::get('/report', [\App\Http\Controllers\Disclosure\GriReportController::class, 'preview'])->name('report.preview');
            Route::get('/report/pdf', [\App\Http\Controllers\Disclosure\GriReportController::class, 'exportPdf'])->name('report.pdf');
            Route::get('/content-index.csv', [\App\Http\Controllers\Disclosure\GriReportController::class, 'exportContentIndex'])->name('content-index');
        });

        Route::get('/esg-dashboard', [\App\Http\Controllers\Disclosure\EsgDashboardController::class, 'index'])->name('esg-dashboard');
    });

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/show', [ReportController::class, 'show'])->name('show');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ReportController::class, 'exportPDF'])->name('export.pdf');
        Route::get('/export/ieqt', [\App\Http\Controllers\IeqtExportController::class, 'export'])->name('export.ieqt');
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
        Route::post('/invitations/{invitation}/resend', [\App\Http\Controllers\StaffManagementController::class, 'resendInvitation'])->name('resend-invitation');
        Route::put('/{access}/role', [\App\Http\Controllers\StaffManagementController::class, 'updateRole'])->name('update-role');
        Route::delete('/{access}', [\App\Http\Controllers\StaffManagementController::class, 'destroy'])->name('destroy');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\StaffManagementController::class, 'cancelInvitation'])->name('cancel-invitation');
    });
    
    // Subscription & Billing routes (not for partner-managed client workspaces)
    Route::prefix('subscriptions')->name('subscriptions.')->middleware('restrictManagedClientBilling')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\SubscriptionController::class, 'index'])->name('index');
        Route::get('/current-plan', [\App\Http\Controllers\Client\SubscriptionController::class, 'currentPlan'])->name('current-plan');
        Route::get('/upgrade', [\App\Http\Controllers\Client\SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [\App\Http\Controllers\Client\SubscriptionController::class, 'processUpgrade'])->name('process-upgrade');

        // Payment checkout + gateway callbacks
        Route::get('/checkout/{id}', [\App\Http\Controllers\Client\SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/payment/razorpay/callback', [\App\Http\Controllers\Client\SubscriptionController::class, 'razorpayCallback'])->name('payment.razorpay');
        Route::get('/payment/cashfree/callback', [\App\Http\Controllers\Client\SubscriptionController::class, 'cashfreeCallback'])->name('payment.cashfree');
        Route::get('/billing', [\App\Http\Controllers\Client\SubscriptionController::class, 'billing'])->name('billing');
        Route::get('/payment-history', [\App\Http\Controllers\Client\SubscriptionController::class, 'paymentHistory'])->name('payment-history');
        Route::post('/cancel', [\App\Http\Controllers\Client\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [\App\Http\Controllers\Client\SubscriptionController::class, 'resume'])->name('resume');
        
        // Billing Methods routes
        Route::post('/billing-methods', [\App\Http\Controllers\Client\SubscriptionController::class, 'storeBillingMethod'])->name('billing-methods.store');
        Route::put('/billing-methods/{billingMethod}', [\App\Http\Controllers\Client\SubscriptionController::class, 'updateBillingMethod'])->name('billing-methods.update');
        Route::delete('/billing-methods/{billingMethod}', [\App\Http\Controllers\Client\SubscriptionController::class, 'destroyBillingMethod'])->name('billing-methods.destroy');
        Route::post('/billing-methods/{billingMethod}/set-default', [\App\Http\Controllers\Client\SubscriptionController::class, 'setDefaultBillingMethod'])->name('billing-methods.set-default');
    });

    // Consultant directory (plan-gated visibility) + marketplace checkout (C10)
    Route::prefix('consultants')->name('client.consultants.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\ConsultantDirectoryController::class, 'index'])->name('index');
        Route::get('/orders', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'orders'])->name('orders');
        Route::get('/payment/checkout/{id}', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'paymentCheckout'])->name('payment.checkout');
        Route::post('/payment/razorpay', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'razorpayCallback'])->name('payment.razorpay');
        Route::get('/payment/cashfree', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'cashfreeCallback'])->name('payment.cashfree');
        Route::get('/{consultant}/checkout', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'checkout'])->name('checkout');
        Route::post('/{consultant}/checkout', [\App\Http\Controllers\Client\ConsultantMarketplaceController::class, 'processCheckout'])->name('checkout.process');
        Route::get('/{consultant}', [\App\Http\Controllers\Client\ConsultantDirectoryController::class, 'show'])->name('show');
        Route::post('/{consultant}/intro', [\App\Http\Controllers\Client\ConsultantDirectoryController::class, 'requestIntro'])->name('intro');
    });
    
});

// Payment gateway webhooks (public, signature-verified, CSRF-exempt)
Route::prefix('webhooks/payments')->name('webhooks.payments.')->group(function () {
    Route::post('/razorpay', [\App\Http\Controllers\PaymentWebhookController::class, 'razorpay'])->name('razorpay');
    Route::post('/cashfree', [\App\Http\Controllers\PaymentWebhookController::class, 'cashfree'])->name('cashfree');
});

// API routes for dynamic industry category dropdowns
Route::get('/api/industries', function(Request $request) {
    try {
        $sectorId = $request->get('sector_id');
        if (!$sectorId) {
            return response()->json([]);
        }
        $industries = \App\Models\MasterIndustryCategory::getIndustriesBySector($sectorId);
        return response()->json($industries->map(function($item) {
            return ['id' => $item->id, 'name' => $item->name];
        }));
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch industries'], 500);
    }
});

Route::get('/api/subcategories', function(Request $request) {
    try {
        $industryId = $request->get('industry_id');
        if (!$industryId) {
            return response()->json([]);
        }
        $subcategories = \App\Models\MasterIndustryCategory::getSubcategoriesByIndustry($industryId);
        return response()->json($subcategories->map(function($item) {
            return ['id' => $item->id, 'name' => $item->name];
        }));
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch subcategories'], 500);
    }
});

// Partner / Agency hub (P16+) — web guard + company_type = partner
Route::prefix('partner')->middleware(['auth:web', 'setActiveCompany', 'checkCompanyType:partner'])->name('partner.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Partner\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/workspace', [\App\Http\Controllers\Partner\WorkspaceController::class, 'switcher'])->name('workspace.switcher');
    Route::post('/workspace/enter/{engagement}', [\App\Http\Controllers\Partner\WorkspaceController::class, 'enter'])->name('workspace.enter');
    Route::post('/workspace/enter-readonly/{engagement}', [\App\Http\Controllers\Partner\WorkspaceController::class, 'enterReadOnly'])->name('workspace.enter-readonly');
    Route::post('/workspace/exit', [\App\Http\Controllers\Partner\WorkspaceController::class, 'exit'])->name('workspace.exit');

    Route::resource('clients', \App\Http\Controllers\Partner\ManagedClientController::class);
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
        Route::post('/companies/{company}/grant-subscription', [\App\Http\Controllers\Admin\CompanySubscriptionController::class, 'grant'])->name('companies.grant-subscription');

        // Campaign coupons
        Route::get('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [\App\Http\Controllers\Admin\CouponController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [\App\Http\Controllers\Admin\CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'destroy'])->name('coupons.destroy');
        
        // Subscription Plans Management
        Route::get('/subscription-plans', [\App\Http\Controllers\Admin\SuperAdminController::class, 'subscriptionPlans'])->name('subscription-plans');
        Route::get('/subscription-plans/create', [\App\Http\Controllers\Admin\SuperAdminController::class, 'createSubscriptionPlan'])->name('subscription-plans.create');
        Route::post('/subscription-plans', [\App\Http\Controllers\Admin\SuperAdminController::class, 'storeSubscriptionPlan'])->name('subscription-plans.store');
        Route::get('/subscription-plans/{id}/edit', [\App\Http\Controllers\Admin\SuperAdminController::class, 'editSubscriptionPlan'])->name('subscription-plans.edit');
        Route::put('/subscription-plans/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'updateSubscriptionPlan'])->name('subscription-plans.update');
        Route::delete('/subscription-plans/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'destroySubscriptionPlan'])->name('subscription-plans.destroy');
        Route::get('/subscription-plans/{id}/entitlements', [\App\Http\Controllers\Admin\PlanEntitlementController::class, 'edit'])->name('subscription-plans.entitlements');
        Route::put('/subscription-plans/{id}/entitlements', [\App\Http\Controllers\Admin\PlanEntitlementController::class, 'update'])->name('subscription-plans.entitlements.update');
        Route::post('/subscription-plans/{id}/entitlements/reset', [\App\Http\Controllers\Admin\PlanEntitlementController::class, 'resetDefaults'])->name('subscription-plans.entitlements.reset');

        // Consultant directory (Phase A) + marketplace orders stub (C10)
        Route::prefix('consultants')->name('consultants.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ConsultantController::class, 'index'])->name('index');
            Route::get('/intro-requests', [\App\Http\Controllers\Admin\ConsultantIntroRequestController::class, 'index'])->name('intro-requests');
            Route::put('/intro-requests/{introRequest}', [\App\Http\Controllers\Admin\ConsultantIntroRequestController::class, 'update'])->name('intro-requests.update');
            Route::get('/orders', [\App\Http\Controllers\Admin\ConsultantOrderController::class, 'index'])->name('orders');
            Route::post('/orders/{order}/deliver', [\App\Http\Controllers\Admin\ConsultantOrderController::class, 'markDelivered'])->name('orders.deliver');
            Route::post('/orders/{order}/release', [\App\Http\Controllers\Admin\ConsultantOrderController::class, 'releaseEscrow'])->name('orders.release');
            Route::post('/orders/{order}/refund', [\App\Http\Controllers\Admin\ConsultantOrderController::class, 'refundEscrow'])->name('orders.refund');
            Route::get('/{consultant}', [\App\Http\Controllers\Admin\ConsultantController::class, 'show'])->name('show');
            Route::post('/{consultant}/approve', [\App\Http\Controllers\Admin\ConsultantController::class, 'approve'])->name('approve');
            Route::post('/{consultant}/reject', [\App\Http\Controllers\Admin\ConsultantController::class, 'reject'])->name('reject');
            Route::post('/{consultant}/suspend', [\App\Http\Controllers\Admin\ConsultantController::class, 'suspend'])->name('suspend');
            Route::post('/{consultant}/featured', [\App\Http\Controllers\Admin\ConsultantController::class, 'toggleFeatured'])->name('featured');
            Route::put('/{consultant}/notes', [\App\Http\Controllers\Admin\ConsultantController::class, 'updateNotes'])->name('notes');
            Route::get('/{consultant}/documents/{documentId}/download', [\App\Http\Controllers\Admin\ConsultantController::class, 'downloadDocument'])->name('documents.download');
        });
        
        // Pricing Page Content Management (comparison table + Scope 3 add-ons)
        Route::prefix('pricing')->name('pricing.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PricingContentController::class, 'index'])->name('index');

            Route::get('/feature-rows/create', [\App\Http\Controllers\Admin\PricingContentController::class, 'createFeatureRow'])->name('feature-rows.create');
            Route::post('/feature-rows', [\App\Http\Controllers\Admin\PricingContentController::class, 'storeFeatureRow'])->name('feature-rows.store');
            Route::get('/feature-rows/{id}/edit', [\App\Http\Controllers\Admin\PricingContentController::class, 'editFeatureRow'])->name('feature-rows.edit');
            Route::put('/feature-rows/{id}', [\App\Http\Controllers\Admin\PricingContentController::class, 'updateFeatureRow'])->name('feature-rows.update');
            Route::delete('/feature-rows/{id}', [\App\Http\Controllers\Admin\PricingContentController::class, 'destroyFeatureRow'])->name('feature-rows.destroy');

            Route::get('/addons/create', [\App\Http\Controllers\Admin\PricingContentController::class, 'createAddon'])->name('addons.create');
            Route::post('/addons', [\App\Http\Controllers\Admin\PricingContentController::class, 'storeAddon'])->name('addons.store');
            Route::get('/addons/{id}/edit', [\App\Http\Controllers\Admin\PricingContentController::class, 'editAddon'])->name('addons.edit');
            Route::put('/addons/{id}', [\App\Http\Controllers\Admin\PricingContentController::class, 'updateAddon'])->name('addons.update');
            Route::delete('/addons/{id}', [\App\Http\Controllers\Admin\PricingContentController::class, 'destroyAddon'])->name('addons.destroy');
        });

        // Payment Gateway Settings (Razorpay / Cashfree credentials)
        Route::get('/payment-gateways', [\App\Http\Controllers\Admin\PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::put('/payment-gateways/{id}', [\App\Http\Controllers\Admin\PaymentGatewayController::class, 'update'])->name('payment-gateways.update');

        // Site Content (company/contact details, currency, policy pages)
        Route::get('/site-content', [\App\Http\Controllers\Admin\SiteContentController::class, 'index'])->name('site-content.index');
        Route::put('/site-content/settings', [\App\Http\Controllers\Admin\SiteContentController::class, 'updateSettings'])->name('site-content.settings');
        Route::get('/site-content/pages/{id}/edit', [\App\Http\Controllers\Admin\SiteContentController::class, 'editPage'])->name('site-content.pages.edit');
        Route::put('/site-content/pages/{id}', [\App\Http\Controllers\Admin\SiteContentController::class, 'updatePage'])->name('site-content.pages.update');

        // Role Templates Management
        Route::get('/role-templates', [\App\Http\Controllers\Admin\SuperAdminController::class, 'roleTemplates'])->name('role-templates');
        Route::get('/role-templates/create', [\App\Http\Controllers\Admin\SuperAdminController::class, 'createRoleTemplate'])->name('role-templates.create');
        Route::post('/role-templates', [\App\Http\Controllers\Admin\SuperAdminController::class, 'storeRoleTemplate'])->name('role-templates.store');
        Route::get('/role-templates/{id}/edit', [\App\Http\Controllers\Admin\SuperAdminController::class, 'editRoleTemplate'])->name('role-templates.edit');
        Route::put('/role-templates/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'updateRoleTemplate'])->name('role-templates.update');
        Route::delete('/role-templates/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'destroyRoleTemplate'])->name('role-templates.destroy');
        
        // Users Management
        Route::get('/users', [\App\Http\Controllers\Admin\SuperAdminController::class, 'users'])->name('users.index');
        Route::get('/users/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'showUser'])->name('users.show');
        
        // Statistics
        Route::get('/statistics', [\App\Http\Controllers\Admin\SuperAdminController::class, 'statistics'])->name('statistics');
        
        // Emission Management
        Route::prefix('emissions')->name('emissions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'index'])->name('index');
            
            // Emission Sources
            Route::get('/sources', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'sources'])->name('sources');
            Route::get('/sources/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createSource'])->name('sources.create');
            Route::post('/sources', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeSource'])->name('sources.store');
            Route::get('/sources/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editSource'])->name('sources.edit');
            Route::put('/sources/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateSource'])->name('sources.update');
            Route::delete('/sources/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroySource'])->name('sources.destroy');
            
            // Emission Factors
            Route::get('/factors', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'factors'])->name('factors');
            Route::get('/factors/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createFactor'])->name('factors.create');
            Route::post('/factors', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeFactor'])->name('factors.store');
            Route::get('/factors/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editFactor'])->name('factors.edit');
            Route::put('/factors/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateFactor'])->name('factors.update');
            Route::delete('/factors/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroyFactor'])->name('factors.destroy');
            
            // GWP Values
            Route::get('/gwp-values', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'gwpValues'])->name('gwp-values');
            Route::get('/gwp-values/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createGwpValue'])->name('gwp-values.create');
            Route::post('/gwp-values', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeGwpValue'])->name('gwp-values.store');
            Route::get('/gwp-values/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editGwpValue'])->name('gwp-values.edit');
            Route::put('/gwp-values/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateGwpValue'])->name('gwp-values.update');
            Route::delete('/gwp-values/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroyGwpValue'])->name('gwp-values.destroy');
            
            // Unit Conversions
            Route::get('/unit-conversions', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'unitConversions'])->name('unit-conversions');
            Route::get('/unit-conversions/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createUnitConversion'])->name('unit-conversions.create');
            Route::post('/unit-conversions', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeUnitConversion'])->name('unit-conversions.store');
            Route::get('/unit-conversions/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editUnitConversion'])->name('unit-conversions.edit');
            Route::put('/unit-conversions/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateUnitConversion'])->name('unit-conversions.update');
            Route::delete('/unit-conversions/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroyUnitConversion'])->name('unit-conversions.destroy');
            
            // Industry Labels
            Route::get('/industry-labels', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'industryLabels'])->name('industry-labels');
            Route::get('/industry-labels/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createIndustryLabel'])->name('industry-labels.create');
            Route::post('/industry-labels', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeIndustryLabel'])->name('industry-labels.store');
            Route::get('/industry-labels/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editIndustryLabel'])->name('industry-labels.edit');
            Route::put('/industry-labels/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateIndustryLabel'])->name('industry-labels.update');
            Route::delete('/industry-labels/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroyIndustryLabel'])->name('industry-labels.destroy');
            
            // Selection Rules
            Route::get('/selection-rules', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'selectionRules'])->name('selection-rules');
            Route::get('/selection-rules/create', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'createSelectionRule'])->name('selection-rules.create');
            Route::post('/selection-rules', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'storeSelectionRule'])->name('selection-rules.store');
            Route::get('/selection-rules/{id}/edit', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'editSelectionRule'])->name('selection-rules.edit');
            Route::put('/selection-rules/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'updateSelectionRule'])->name('selection-rules.update');
            Route::delete('/selection-rules/{id}', [\App\Http\Controllers\Admin\EmissionManagementController::class, 'destroySelectionRule'])->name('selection-rules.destroy');
        });
    });
