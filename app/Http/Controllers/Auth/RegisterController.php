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
            'company_name' => 'required|string|max:255',
        ]);

        // Check if company already exists, if not create it
        $company = Company::where('name', $request->company_name)->first();
        
        if (!$company) {
            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'is_active' => true,
            ]);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Account created successfully!');
    }
}
