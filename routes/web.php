<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmissionFormController;
use App\Http\Controllers\EmissionManagementController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EmissionBoundaryController;
use App\Http\Controllers\MeasurementController;

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
        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// Company setup routes (accessible after registration)
Route::middleware('auth')->group(function () {
    Route::get('/company/setup', [CompanySetupController::class, 'index'])->name('company.setup');
    Route::post('/company/setup', [CompanySetupController::class, 'store'])->name('company.setup.store');
    Route::get('/company/setup/skip', [CompanySetupController::class, 'skip'])->name('company.setup.skip');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
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
    
    // Measurements routes (replacing old emission form)
    Route::resource('measurements', MeasurementController::class)->except(['create', 'store']);
    Route::post('/measurements/{measurement}/submit', [MeasurementController::class, 'submit'])->name('measurements.submit');
    
    // Debug route to test measurement show
    Route::get('/measurements/{measurement}/debug', function($measurementId) {
        $measurement = \App\Models\Measurement::find($measurementId);
        if (!$measurement) {
            return 'Measurement not found';
        }
        return 'Measurement found: ID=' . $measurement->id . ', Location=' . $measurement->location_id;
    })->name('measurements.debug');
    
    // Debug route to test measurement creation
    Route::get('/debug/measurement', function() {
        $user = \Auth::user();
        $location = \App\Models\Location::where('company_id', $user->company_id)->first();
        
        if (!$location) {
            return 'No location found for company: ' . $user->company_id;
        }
        
        // Use a unique period to avoid duplicates
        $uniqueId = time();
        $measurement = \App\Models\Measurement::create([
            'location_id' => $location->id,
            'period_start' => '2024-12-01',
            'period_end' => '2024-12-31',
            'frequency' => 'monthly',
            'status' => 'draft',
            'fiscal_year' => 2024,
            'fiscal_year_start_month' => 'JAN',
            'created_by' => $user->id,
            'notes' => 'Debug test measurement - ' . $uniqueId,
        ]);
        
        return redirect()->route('measurements.show', $measurement);
    })->name('debug.measurement');
    
    // Debug route to test show method directly
    Route::get('/debug/show/{measurement}', function($measurementId) {
        $measurement = \App\Models\Measurement::find($measurementId);
        if (!$measurement) {
            return 'Measurement not found';
        }
        
        $controller = new \App\Http\Controllers\MeasurementController();
        return $controller->show($measurement);
    })->name('debug.show');
    
    // Simple debug route to test show method with minimal logic
    Route::get('/debug/simple-show/{measurement}', function($measurementId) {
        $measurement = \App\Models\Measurement::find($measurementId);
        if (!$measurement) {
            return 'Measurement not found';
        }
        
        $user = \Auth::user();
        
        // Check if user has access to this measurement
        if ($measurement->location->company_id !== $user->company_id) {
            return 'Unauthorized access to this measurement.';
        }

        $measurement->load(['location']);

        // Get emission boundaries for this location
        $emissionBoundaries = $measurement->location->emissionBoundaries()
            ->get()
            ->groupBy('scope');

        return view('measurements.show', compact('measurement', 'emissionBoundaries'));
    })->name('debug.simple-show');
    
    // Debug route to check emission boundaries
    Route::get('/debug/boundaries/{measurement}', function($measurementId) {
        $measurement = \App\Models\Measurement::find($measurementId);
        if (!$measurement) {
            return 'Measurement not found';
        }
        
        $location = $measurement->location;
        $boundaries = $location->emissionBoundaries()->get();
        
        return response()->json([
            'location_id' => $location->id,
            'location_name' => $location->name,
            'boundaries_count' => $boundaries->count(),
            'boundaries' => $boundaries->toArray(),
            'emission_sources_master_count' => \App\Models\EmissionSourceMaster::count(),
            'emission_sources_master' => \App\Models\EmissionSourceMaster::take(5)->get()->toArray()
        ]);
    })->name('debug.boundaries');
    
    // Debug route to test measurement creation
    Route::get('/debug/sync-measurements/{location}', function($locationId) {
        $location = \App\Models\Location::find($locationId);
        if (!$location) {
            return 'Location not found';
        }
        
        $service = app(\App\Services\MeasurementPeriodService::class);
        
        try {
            $result = $service->syncMeasurementPeriods($location, 1);
            
            return response()->json([
                'success' => true,
                'location_id' => $location->id,
                'location_name' => $location->name,
                'measurement_frequency' => $location->measurement_frequency,
                'fiscal_year_start' => $location->fiscal_year_start,
                'reporting_period' => $location->reporting_period,
                'result' => $result,
                'measurements' => $location->measurements()->orderBy('period_start')->get(['id', 'period_start', 'period_end', 'frequency', 'status'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->name('debug.sync-measurements');
    
    // Debug route to test location update
    Route::post('/debug/location-update/{location}', function($locationId, \Illuminate\Http\Request $request) {
        $location = \App\Models\Location::find($locationId);
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }
        
        try {
            $updateData = [
                'name' => $request->name,
                'measurement_frequency' => $request->measurement_frequency,
                'reporting_period' => $request->reporting_period,
                'fiscal_year_start' => $request->fiscal_year_start,
            ];
            
            $location->update($updateData);
            
            return response()->json([
                'success' => true,
                'location_id' => $location->id,
                'updated_data' => $updateData,
                'current_values' => [
                    'measurement_frequency' => $location->measurement_frequency,
                    'reporting_period' => $location->reporting_period,
                    'fiscal_year_start' => $location->fiscal_year_start,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->name('debug.location-update');
    
    // Debug route to test form submission
    Route::post('/debug/form-test', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'method' => $request->method(),
            'data' => $request->all(),
            'headers' => $request->headers->all(),
            'csrf_token' => $request->header('X-CSRF-TOKEN'),
            'session_token' => csrf_token()
        ]);
    })->name('debug.form-test');
    
    // Emission source calculation routes
    Route::get('/measurements/{measurement}/sources/{source}/calculate', [MeasurementController::class, 'calculateSource'])->name('measurements.calculate-source');
    Route::post('/measurements/{measurement}/sources/{source}/calculate', [MeasurementController::class, 'storeSourceData'])->name('measurements.store-source-data');
    Route::get('/measurements/{measurement}/sources/{source}/edit', [MeasurementController::class, 'editSource'])->name('measurements.edit-source');
    Route::put('/measurements/{measurement}/sources/{source}/edit', [MeasurementController::class, 'updateSourceData'])->name('measurements.update-source-data');
    Route::delete('/measurements/{measurement}/sources/{source}', [MeasurementController::class, 'deleteSourceData'])->name('measurements.delete-source-data');
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Emissions routes
    Route::prefix('emissions')->name('emissions.')->group(function () {
        Route::get('/', function () {
            return view('emissions.index');
        })->name('index');
        Route::get('/create', function () {
            return view('emissions.create');
        })->name('create');
    });
    
    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('reports.index');
        })->name('index');
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
    
    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });
});
