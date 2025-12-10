<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class ForgotPasswordController extends Controller
{
    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with(['status' => __($status)]);
            } else {
                return back()->withErrors(['email' => __($status)]);
            }
        } catch (\Exception $e) {
            // If SMTP is not configured, show the reset link directly
            if (strpos($e->getMessage(), 'SMTP') !== false || 
                strpos($e->getMessage(), 'smtp') !== false ||
                strpos($e->getMessage(), 'getaddrinfo') !== false ||
                strpos($e->getMessage(), 'Connection could not be established') !== false) {
                
                // Get the user
                $user = User::where('email', $request->email)->first();
                
                if (!$user) {
                    return back()->withErrors(['email' => 'We could not find a user with that email address.']);
                }

                // Create password reset token manually
                $token = Password::createToken($user);
                
                // Generate reset URL
                $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
                
                // Redirect to success page with reset link
                return redirect()->route('password.reset-success')
                    ->with('resetUrl', $resetUrl)
                    ->with('email', $user->email);
            }
            
            // Other errors
            return back()->withErrors(['email' => 'An error occurred. Please try again later.']);
        }
    }

    /**
     * Show password reset success page with link (when SMTP not configured)
     */
    public function resetSuccess()
    {
        $resetUrl = session('resetUrl');
        $email = session('email');
        
        if (!$resetUrl || !$email) {
            return redirect()->route('password.request')
                ->with('error', 'Invalid reset request.');
        }
        
        return view('auth.password-reset-success', compact('resetUrl', 'email'));
    }

    /**
     * Show reset password form
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Reset password
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
