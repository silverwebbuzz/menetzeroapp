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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user without company (defaults to client)
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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user without company (will be set as partner during company setup)
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
