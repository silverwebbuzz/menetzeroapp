<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists with this Google ID
            $user = User::where('google_id', $googleUser->getId())->first();
            
            if ($user) {
                // User exists, log them in
                Auth::login($user, true);
                return redirect()->intended(route('dashboard'));
            }
            
            // Check if user exists with this email
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                // User exists with email but no Google ID, update their record
                $existingUser->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'provider' => 'google',
                ]);
                
                Auth::login($existingUser, true);
                return redirect()->intended(route('dashboard'));
            }
            
            // Create new user
            $newUser = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'provider' => 'google',
                'password' => Hash::make(Str::random(24)), // Random password for OAuth users
                'email_verified_at' => now(), // Google emails are pre-verified
                'role' => 'company_user',
                'is_active' => true,
            ]);
            
            Auth::login($newUser, true);
            return redirect()->intended(route('dashboard'));
            
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again.'
            ]);
        }
    }
}
