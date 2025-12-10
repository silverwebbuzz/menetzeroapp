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
    public function redirectToGoogle(Request $request)
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Client OAuth - use User model
            $user = User::where('google_id', $googleUser->getId())->first();
            
            if ($user) {
                // User exists, log them in
                Auth::guard('web')->login($user, true);
                
                // Check if user has multiple company access
                if ($user->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                return redirect()->route('client.dashboard');
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
                
                Auth::guard('web')->login($existingUser, true);
                
                // Check if user has multiple company access
                if ($existingUser->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                return redirect()->route('client.dashboard');
            }
            
            // Create new client user
            $newUser = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'provider' => 'google',
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
                'role' => 'company_user',
                'is_active' => true,
            ]);
            
            Auth::guard('web')->login($newUser, true);
            
            return redirect()->route('company.setup')
                ->with('success', 'Account created successfully! Please complete your business profile to get started.');
            
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
}
