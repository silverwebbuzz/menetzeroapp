<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
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
                
                // Check if user has multiple company access
                if ($user->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                // Redirect to appropriate dashboard based on company type
                return $this->redirectToDashboard($user);
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
                
                // Check if user has multiple company access
                if ($existingUser->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                // Redirect to appropriate dashboard based on company type
                return $this->redirectToDashboard($existingUser);
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
            // Redirect to appropriate dashboard based on company type
            return $this->redirectToDashboard($newUser);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('Google OAuth Connection Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to connect to Google. Please check your internet connection and try again.'
            ]);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // Session state mismatch (common with multiple tabs or expired sessions)
            Log::error('Google OAuth Invalid State', [
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return redirect()->route('login')->withErrors([
                'email' => 'Session expired. Please try logging in again.'
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // OAuth configuration error (wrong credentials, redirect URI mismatch, etc.)
            $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            Log::error('Google OAuth Client Error', [
                'message' => $e->getMessage(),
                'response' => $errorMessage,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'exception' => get_class($e)
            ]);
            
            // Check for specific error types
            if (str_contains($errorMessage, 'redirect_uri_mismatch')) {
                return redirect()->route('login')->withErrors([
                    'email' => 'OAuth configuration error: Redirect URI mismatch. Please contact support.'
                ]);
            } elseif (str_contains($errorMessage, 'invalid_client')) {
                return redirect()->route('login')->withErrors([
                    'email' => 'OAuth configuration error: Invalid client credentials. Please contact support.'
                ]);
            }
            
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication configuration error. Please contact support.'
            ]);
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            // In development, show more details
            if (config('app.debug')) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Google authentication failed: ' . $e->getMessage()
                ]);
            }
            
            // In production, show generic message
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again or use email/password login.'
            ]);
        }
    }

    /**
     * Redirect to appropriate dashboard based on user's company type
     */
    protected function redirectToDashboard($user)
    {
        // If user has a company, check company type
        if ($user->company_id && $user->company) {
            $companyType = $user->company->company_type ?? 'client';
            
            if ($companyType === 'partner') {
                return redirect()->route('partner.dashboard');
            }
        }
        
        // Default to client dashboard
        return redirect()->route('client.dashboard');
    }
}
