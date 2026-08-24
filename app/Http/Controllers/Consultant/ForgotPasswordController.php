<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Consultants use their own broker/token table — see config/auth.php.
     */
    protected function broker()
    {
        return Password::broker('consultants');
    }

    public function showLinkRequestForm()
    {
        if (Auth::guard('consultant')->check()) {
            return redirect()->route('consultant.dashboard');
        }

        return view('consultant.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = $this->broker()->sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }

            return back()->withInput()->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            Log::error('Consultant password reset email failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'email' => 'We were unable to send the reset email. Please try again later.',
            ]);
        }
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('consultant.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Consultant $consultant, string $password) {
                $consultant->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $consultant->save();

                // Consultants also own a mirrored web User (ConsultantAccountService)
                // used for client workspaces — keep both credentials in step.
                $consultant->syncLinkedUserPassword();

                event(new PasswordReset($consultant));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('consultant.login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
