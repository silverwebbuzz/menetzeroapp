<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Services\ConsultantAccountService;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected function shouldCompleteProfile(Consultant $consultant): bool
    {
        return $consultant->status === 'draft'
            && (
                blank($consultant->bio)
                || !is_array($consultant->emirates) || count($consultant->emirates) === 0
                || !is_array($consultant->specialties) || count($consultant->specialties) === 0
            );
    }

    protected function postLoginRedirect(Consultant $consultant)
    {
        if ($this->shouldCompleteProfile($consultant)) {
            return redirect()->route('consultant.profile.edit')
                ->with('info', 'Complete your consultant profile to continue onboarding.');
        }

        return redirect()->intended(route('consultant.dashboard'));
    }

    public function landing()
    {
        if (Auth::guard('consultant')->check()) {
            /** @var Consultant $consultant */
            $consultant = Auth::guard('consultant')->user();

            return $this->postLoginRedirect($consultant);
        }

        $consultantCount = Consultant::query()->where('status', 'approved')->where('is_active', true)->count();

        return view('consultant.landing', compact('consultantCount'));
    }

    public function showRegister()
    {
        if (Auth::guard('consultant')->check()) {
            /** @var Consultant $consultant */
            $consultant = Auth::guard('consultant')->user();

            return $this->postLoginRedirect($consultant);
        }

        return view('consultant.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:consultants,email',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $consultant = Consultant::create([
            ...$data,
            'status' => 'draft',
            'is_active' => true,
        ]);

        Auth::guard('consultant')->login($consultant);
        app(ConsultantAccountService::class)->syncWebSession($consultant);

        try {
            app(EmailTemplateService::class)->sendToConsultant('welcome_consultant', $consultant);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('consultant.profile.edit')
            ->with('success', 'Welcome! Please complete your profile to continue onboarding.');
    }

    public function showLogin()
    {
        if (Auth::guard('consultant')->check()) {
            /** @var Consultant $consultant */
            $consultant = Auth::guard('consultant')->user();

            return $this->postLoginRedirect($consultant);
        }

        return view('consultant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $consultant = Consultant::where('email', $credentials['email'])->first();

        if (!$consultant || !$consultant->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (Auth::guard('consultant')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            app(ConsultantAccountService::class)->syncWebSession($consultant);

            return $this->postLoginRedirect($consultant);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('consultant')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('consultant.landing');
    }
}
