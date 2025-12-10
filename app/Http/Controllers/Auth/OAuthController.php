<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PartnerUser;
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
        // Store the registration type in session (client or partner)
        $type = $request->get('type', 'client'); // Default to client if not specified
        session(['oauth_registration_type' => $type]);
        
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
            
            // Get the registration type from session (client or partner)
            $registrationType = session('oauth_registration_type', 'client');
            $isPartner = ($registrationType === 'partner');
            
            if ($isPartner) {
                // Partner OAuth - use PartnerUser model
                $user = PartnerUser::where('google_id', $googleUser->getId())->first();
                
                if ($user) {
                    // User exists, log them in
                    Auth::guard('partner')->login($user, true);
                    session()->forget('oauth_registration_type');
                    
                    // Check if user has multiple company access
                    if ($user->hasMultipleCompanyAccess()) {
                        return redirect()->route('account.selector');
                    }
                    
                    return redirect()->route('partner.dashboard');
                }
                
                // Check if user exists with this email
                $existingUser = PartnerUser::where('email', $googleUser->getEmail())->first();
                
                if ($existingUser) {
                    // User exists with email but no Google ID, update their record
                    $existingUser->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'provider' => 'google',
                    ]);
                    
                    Auth::guard('partner')->login($existingUser, true);
                    session()->forget('oauth_registration_type');
                    
                    // Check if user has multiple company access
                    if ($existingUser->hasMultipleCompanyAccess()) {
                        return redirect()->route('account.selector');
                    }
                    
                    return redirect()->route('partner.dashboard');
                }
                
                // Create new partner user
                $newUser = PartnerUser::create([
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
                
                Auth::guard('partner')->login($newUser, true);
                session(['registering_as_partner' => true]);
                session()->forget('oauth_registration_type');
                
                return redirect()->route('company.setup')
                    ->with('success', 'Account created successfully! Please complete your partner profile to get started.');
            } else {
                // Client OAuth - use User model
                $user = User::where('google_id', $googleUser->getId())->first();
                
                if ($user) {
                    // User exists, log them in
                    Auth::guard('web')->login($user, true);
                    session()->forget('oauth_registration_type');
                    
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
                    session()->forget('oauth_registration_type');
                    
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
                session()->forget('oauth_registration_type');
                
                return redirect()->route('company.setup')
                    ->with('success', 'Account created successfully! Please complete your business profile to get started.');
            }
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network/connection error
            Log::error('Google OAuth Connection Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            $registrationType = session('oauth_registration_type', 'client');
            $isPartner = ($registrationType === 'partner');
            session()->forget('oauth_registration_type');
            
            return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
                'email' => 'Unable to connect to Google. Please check your internet connection and try again.'
            ]);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // Session state mismatch (common with multiple tabs or expired sessions)
            Log::error('Google OAuth Invalid State', [
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            $registrationType = session('oauth_registration_type', 'client');
            $isPartner = ($registrationType === 'partner');
            session()->forget('oauth_registration_type');
            
            return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
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
            $registrationType = session('oauth_registration_type', 'client');
            $isPartner = ($registrationType === 'partner');
            session()->forget('oauth_registration_type');
            
            if (str_contains($errorMessage, 'redirect_uri_mismatch')) {
                return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
                    'email' => 'OAuth configuration error: Redirect URI mismatch. Please contact support.'
                ]);
            } elseif (str_contains($errorMessage, 'invalid_client')) {
                return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
                    'email' => 'OAuth configuration error: Invalid client credentials. Please contact support.'
                ]);
            }
            
            return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
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
            
            $registrationType = session('oauth_registration_type', 'client');
            $isPartner = ($registrationType === 'partner');
            session()->forget('oauth_registration_type');
            
            // In development, show more details
            if (config('app.debug')) {
                return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
                    'email' => 'Google authentication failed: ' . $e->getMessage()
                ]);
            }
            
            // In production, show generic message
            return redirect()->route($isPartner ? 'partner.login' : 'login')->withErrors([
                'email' => 'Google authentication failed. Please try again or use email/password login.'
            ]);
        }
    }

    /**
     * Redirect to appropriate dashboard based on user's company type
     */
    protected function redirectToDashboard($user, $isPartner = false)
    {
        // If user has a company, check company type
        if ($user->company_id && $user->company) {
            $companyType = $user->company->company_type ?? 'client';
            
            if ($companyType === 'partner') {
                return redirect()->route('partner.dashboard');
            }
        }
        
        // If user doesn't have company yet, redirect to setup
        if (!$user->company_id) {
            if ($isPartner) {
                session(['registering_as_partner' => true]);
            }
            return redirect()->route('company.setup')
                ->with('success', 'Please complete your business profile to get started.');
        }
        
        // Default to client dashboard
        return redirect()->route('client.dashboard');
    }
}
