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
            
            // Check if user already exists with this Google ID
            $user = User::where('google_id', $googleUser->getId())->first();
            
            if ($user) {
                // User exists, validate company type matches registration type BEFORE logging in
                if ($user->company_id) {
                    $company = $user->company;
                    if ($company) {
                        // Validate type match
                        if ($isPartner && $company->company_type !== 'partner') {
                            session()->forget('oauth_registration_type');
                            return redirect()->route('partner.login')
                                ->withErrors(['email' => 'This account is registered as a Client. Please login from the Client login page.']);
                        }
                        
                        if (!$isPartner && $company->company_type === 'partner') {
                            session()->forget('oauth_registration_type');
                            return redirect()->route('login')
                                ->withErrors(['email' => 'This account is registered as a Partner. Please login from the Partner login page.']);
                        }
                    }
                }
                
                // User exists and type matches (or no company yet), log them in
                Auth::login($user, true);
                session()->forget('oauth_registration_type');
                
                // Check if user has multiple company access
                if ($user->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                // Redirect to appropriate dashboard based on company type
                return $this->redirectToDashboard($user, $isPartner);
            }
            
            // Check if user exists with this email
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                // Validate company type if user has a company
                if ($existingUser->company_id) {
                    $company = $existingUser->company;
                    if ($company) {
                        // Check type match
                        if ($isPartner && $company->company_type !== 'partner') {
                            session()->forget('oauth_registration_type');
                            return redirect()->route('partner.login')
                                ->withErrors(['email' => 'This email is registered as a Client. Please login from the Client login page.']);
                        }
                        
                        if (!$isPartner && $company->company_type === 'partner') {
                            session()->forget('oauth_registration_type');
                            return redirect()->route('login')
                                ->withErrors(['email' => 'This email is registered as a Partner. Please login from the Partner login page.']);
                        }
                    }
                }
                
                // User exists with email but no Google ID, update their record
                $existingUser->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'provider' => 'google',
                ]);
                
                Auth::login($existingUser, true);
                session()->forget('oauth_registration_type');
                
                // Check if user has multiple company access
                if ($existingUser->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector');
                }
                
                // Redirect to appropriate dashboard based on company type
                return $this->redirectToDashboard($existingUser, $isPartner);
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
            
            // Set session flag for company setup based on registration type
            if ($isPartner) {
                session(['registering_as_partner' => true]);
            }
            
            session()->forget('oauth_registration_type');
            
            // Redirect to company setup (user doesn't have company yet)
            return redirect()->route('company.setup')
                ->with('success', 'Account created successfully! Please complete your business profile to get started.');
            
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
