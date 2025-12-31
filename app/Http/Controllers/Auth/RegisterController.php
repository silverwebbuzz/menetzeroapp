<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        // Set role to company_admin since they're registering as a client
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'company_admin', // Client registration = company_admin
            'is_active' => true,
        ]);

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            // Log the error but don't fail registration if email fails
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        Auth::guard('web')->login($user);

        // Check if user has multiple company access (from invitations they accepted before registering)
        if ($user->hasMultipleCompanyAccess()) {
            return redirect()->route('account.selector')
                ->with('success', 'Account created successfully! Select a company to continue.');
        }
        
        // Check if user has company access
        $activeCompany = $user->getActiveCompany();
        if ($activeCompany) {
            return redirect()->route('client.dashboard')
                ->with('success', 'Account created successfully!');
        }

        // No company access - redirect to dashboard (which will show company setup form)
        return redirect()->route('client.dashboard')
            ->with('success', 'Account created successfully! Please complete your business profile to get started.');
    }
}
