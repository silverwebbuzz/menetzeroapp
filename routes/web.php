<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmissionFormController;
use App\Http\Controllers\EmissionManagementController;

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
    
    // Emission Form routes
    Route::prefix('emission-form')->name('emission-form.')->group(function () {
        Route::get('/', [EmissionFormController::class, 'index'])->name('index');
        Route::get('/step/{step}', [EmissionFormController::class, 'showStep'])->name('step');
        Route::post('/step/{step}', [EmissionFormController::class, 'storeStep'])->name('store');
        Route::get('/review', [EmissionFormController::class, 'review'])->name('review');
        Route::post('/submit', [EmissionFormController::class, 'submit'])->name('submit');
        Route::get('/success', [EmissionFormController::class, 'success'])->name('success');
        Route::post('/ocr', [EmissionFormController::class, 'extractOCRData'])->name('ocr');
    });
    
    // Emission Management routes
    Route::prefix('emissions')->name('emissions.')->group(function () {
        Route::get('/management', [EmissionManagementController::class, 'index'])->name('management');
        Route::get('/{emission}', [EmissionManagementController::class, 'show'])->name('show');
        Route::get('/{emission}/edit', [EmissionManagementController::class, 'edit'])->name('edit');
        Route::post('/{emission}/duplicate', [EmissionManagementController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{emission}/delete', [EmissionManagementController::class, 'delete'])->name('delete');
        Route::patch('/{emission}/status', [EmissionManagementController::class, 'updateStatus'])->name('update-status');
    });
    
    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });
});
