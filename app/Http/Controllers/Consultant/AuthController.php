<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function landing()
    {
        if (Auth::guard('consultant')->check()) {
            return redirect()->route('consultant.dashboard');
        }

        $partnerCount = Consultant::query()->where('status', 'approved')->where('is_active', true)->count();

        return view('consultant.landing', compact('partnerCount'));
    }

    public function showRegister()
    {
        if (Auth::guard('consultant')->check()) {
            return redirect()->route('consultant.dashboard');
        }

        return view('consultant.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:consultants,email',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $consultant = Consultant::create([
            ...$data,
            'status' => 'draft',
            'is_active' => true,
        ]);

        Auth::guard('consultant')->login($consultant);

        return redirect()->route('consultant.dashboard')
            ->with('success', 'Welcome! Complete your profile and upload documents to apply for the partner directory.');
    }

    public function showLogin()
    {
        if (Auth::guard('consultant')->check()) {
            return redirect()->route('consultant.dashboard');
        }

        return view('consultant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $consultant = Consultant::where('email', $credentials['email'])->first();

        if (!$consultant || !$consultant->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (Auth::guard('consultant')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('consultant.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('consultant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('consultant.landing');
    }
}
