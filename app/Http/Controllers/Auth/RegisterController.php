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
                'unique:users,email', // Email must be unique in users table (clients)
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create new user in users table (clients only)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'company_user',
            'is_active' => true,
        ]);

        Auth::guard('web')->login($user);

        return redirect()->route('client.dashboard')->with('success', 'Account created successfully! Please complete your business profile to get started.');
    }
}
