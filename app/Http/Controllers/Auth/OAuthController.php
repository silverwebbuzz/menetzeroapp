<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Services\ConsultantAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider.
     */
    public function redirectToGoogle(Request $request)
    {
        $intent = $request->routeIs('consultant.auth.google') ? 'consultant' : 'client';
        session(['oauth_intent' => $intent]);

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        $intent = session()->pull('oauth_intent', 'client');
        $loginRoute = $intent === 'consultant' ? 'consultant.login' : 'login';

        try {
            $googleUser = Socialite::driver('google')->user();

            if ($intent === 'consultant') {
                return $this->handleConsultantGoogleCallback($googleUser);
            }

            return $this->handleClientGoogleCallback($googleUser);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Google OAuth Connection Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Unable to connect to Google. Please check your internet connection and try again.',
            ]);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Google OAuth Invalid State', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Session expired. Please try logging in again.',
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            Log::error('Google OAuth Client Error', [
                'message' => $e->getMessage(),
                'response' => $errorMessage,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'exception' => get_class($e),
            ]);

            if (str_contains($errorMessage, 'redirect_uri_mismatch')) {
                return redirect()->route($loginRoute)->withErrors([
                    'email' => 'OAuth configuration error: Redirect URI mismatch. Please contact support.',
                ]);
            }

            if (str_contains($errorMessage, 'invalid_client')) {
                return redirect()->route($loginRoute)->withErrors([
                    'email' => 'OAuth configuration error: Invalid client credentials. Please contact support.',
                ]);
            }

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Google authentication configuration error. Please contact support.',
            ]);
        } catch (\Exception $e) {
            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            if (config('app.debug')) {
                return redirect()->route($loginRoute)->withErrors([
                    'email' => 'Google authentication failed: ' . $e->getMessage(),
                ]);
            }

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Google authentication failed. Please try again or use email/password login.',
            ]);
        }
    }

    private function handleClientGoogleCallback($googleUser)
    {
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::guard('web')->login($user, true);

            if ($user->hasMultipleCompanyAccess()) {
                return redirect()->route('account.selector');
            }

            return redirect()->route('client.dashboard')
                ->with('info', 'Welcome back. Complete your business profile and location if you have not already.');
        }

        $existingConsultant = Consultant::where('email', $googleUser->getEmail())->first();
        if ($existingConsultant) {
            return redirect()->route('consultant.login')->withErrors([
                'email' => 'This email is registered as a consultant account. Please sign in with Google from the consultant portal.',
            ]);
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            $existingUser->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'provider' => 'google',
            ]);

            Auth::guard('web')->login($existingUser, true);

            if ($existingUser->hasMultipleCompanyAccess()) {
                return redirect()->route('account.selector');
            }

            return redirect()->route('client.dashboard')
                ->with('info', 'Google account linked. Complete your business profile and location if you have not already.');
        }

        $newUser = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'provider' => 'google',
            'password' => Hash::make(Str::random(24)),
            'email_verified_at' => now(),
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        try {
            Mail::to($newUser->email)->send(new WelcomeEmail($newUser));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        Auth::guard('web')->login($newUser, true);

        return redirect()->route('client.dashboard')
            ->with('success', 'Account created! Complete your business profile and add a location before entering emission data.');
    }

    private function handleConsultantGoogleCallback($googleUser)
    {
        $consultant = Consultant::where('google_id', $googleUser->getId())->first();

        if ($consultant) {
            if (!$consultant->is_active) {
                return redirect()->route('consultant.login')->withErrors([
                    'email' => 'This consultant account is inactive. Please contact support.',
                ]);
            }

            Auth::guard('consultant')->login($consultant, true);
            app(ConsultantAccountService::class)->syncWebSession($consultant);

            return redirect()->route('consultant.dashboard')
                ->with('info', 'Welcome back to your consultant portal.');
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        if ($existingUser && !Consultant::where('email', $googleUser->getEmail())->exists()) {
            return redirect()->route('consultant.login')->withErrors([
                'email' => 'This email is registered as a company account. Please use company sign in, or register with a different email for consultant access.',
            ]);
        }

        $existingConsultant = Consultant::where('email', $googleUser->getEmail())->first();

        if ($existingConsultant) {
            if (!$existingConsultant->is_active) {
                return redirect()->route('consultant.login')->withErrors([
                    'email' => 'This consultant account is inactive. Please contact support.',
                ]);
            }

            $existingConsultant->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'provider' => 'google',
            ]);

            Auth::guard('consultant')->login($existingConsultant, true);
            app(ConsultantAccountService::class)->syncWebSession($existingConsultant);

            return redirect()->route('consultant.dashboard')
                ->with('info', 'Google account linked to your consultant profile.');
        }

        $practiceName = trim($googleUser->getName()) !== ''
            ? $googleUser->getName() . ' Practice'
            : 'Consultant Practice';

        $newConsultant = Consultant::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'provider' => 'google',
            'password' => Hash::make(Str::random(24)),
            'company_name' => $practiceName,
            'status' => 'draft',
            'is_active' => true,
        ]);

        Auth::guard('consultant')->login($newConsultant, true);
        app(ConsultantAccountService::class)->syncWebSession($newConsultant);

        return redirect()->route('consultant.dashboard')
            ->with('success', 'Consultant account created! Complete your profile for the directory, or explore agency packs after signing in.');
    }
}
