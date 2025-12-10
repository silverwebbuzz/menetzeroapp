<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if email exists with CLIENT company type
                    $existingUser = User::where('email', $value)->first();
                    
                    if ($existingUser && $existingUser->company_id) {
                        $company = $existingUser->company;
                        if ($company && $company->company_type === 'client') {
                            $fail('This email is already registered as a client. Please login instead or use a different email.');
                        }
                        // If company type is partner, allow registration (different type)
                    }
                },
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if email already exists
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            // If user exists with partner company or no company, verify password
            if ($existingUser->company_id) {
                $company = $existingUser->company;
                // If user has partner company, they can't register as client
                if ($company && $company->company_type === 'partner') {
                    // Allow - different type, but verify password if they're trying to use existing account
                    if (Hash::check($request->password, $existingUser->password)) {
                        // Password matches - this is login attempt, not registration
                        Auth::login($existingUser);
                        return redirect()->route('client.dashboard')
                            ->with('info', 'This email is registered as a partner. Redirected to client dashboard.');
                    }
                }
            }
            
            // If user exists but no company, create new account (shouldn't happen due to validation, but handle it)
            if (!$existingUser->company_id) {
                // User exists but no company - update name if needed and login
                if (Hash::check($request->password, $existingUser->password)) {
                    Auth::login($existingUser);
                    return redirect()->route('client.dashboard')
                        ->with('success', 'Welcome back! Please complete your business profile.');
                }
            }
            
            // Email exists but password doesn't match or different scenario
            return back()->withErrors(['email' => 'This email is already registered. Please login instead.'])->withInput();
        }

        // Create new user without company (defaults to client)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'company_user',
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('client.dashboard')->with('success', 'Account created successfully! Please complete your business profile to get started.');
    }

    public function showPartnerRegistrationForm()
    {
        return view('auth.register', ['isPartner' => true]);
    }

    public function registerPartner(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if email exists with PARTNER company type
                    $existingUser = User::where('email', $value)->first();
                    
                    if ($existingUser && $existingUser->company_id) {
                        $company = $existingUser->company;
                        if ($company && $company->company_type === 'partner') {
                            $fail('This email is already registered as a partner. Please login instead or use a different email.');
                        }
                        // If company type is client, allow registration (different type)
                    }
                },
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if email already exists
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            // If user exists with client company or no company, verify password
            if ($existingUser->company_id) {
                $company = $existingUser->company;
                // If user has client company, they can't register as partner
                if ($company && $company->company_type === 'client') {
                    // Allow - different type, but verify password if they're trying to use existing account
                    if (Hash::check($request->password, $existingUser->password)) {
                        // Password matches - this is login attempt, not registration
                        Auth::login($existingUser);
                        session(['registering_as_partner' => true]);
                        return redirect()->route('company.setup')
                            ->with('info', 'This email is registered as a client. Setting up as partner.');
                    }
                }
            }
            
            // If user exists but no company, create new account (shouldn't happen due to validation, but handle it)
            if (!$existingUser->company_id) {
                // User exists but no company - update name if needed and login
                if (Hash::check($request->password, $existingUser->password)) {
                    Auth::login($existingUser);
                    session(['registering_as_partner' => true]);
                    return redirect()->route('company.setup')
                        ->with('success', 'Welcome back! Please complete your partner profile.');
                }
            }
            
            // Email exists but password doesn't match or different scenario
            return back()->withErrors(['email' => 'This email is already registered. Please login instead.'])->withInput();
        }

        // Create new user without company (will be set as partner during company setup)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'company_user',
            'is_active' => true,
        ]);

        Auth::login($user);
        
        // Set session flag to indicate partner registration
        session(['registering_as_partner' => true]);

        return redirect()->route('company.setup')->with('success', 'Account created successfully! Please complete your partner profile to get started.');
    }
}
