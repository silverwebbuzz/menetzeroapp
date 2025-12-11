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
        // No role field - all roles managed in user_company_roles
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Auth::guard('web')->login($user);

        // Check if user has any existing company access (from invitations they accepted before registering)
        if ($user->hasMultipleCompanyAccess()) {
            return redirect()->route('account.selector')
                ->with('success', 'Account created successfully! Select a workspace to continue.');
        }
        
        // Check if user has single company access
        $activeCompany = $user->getActiveCompany();
        if ($activeCompany) {
            return redirect()->route('client.dashboard')
                ->with('success', 'Account created successfully!');
        }

        // No company access - redirect to company setup
        return redirect()->route('client.dashboard')
            ->with('success', 'Account created successfully! Please complete your business profile to get started.');
    }
}
