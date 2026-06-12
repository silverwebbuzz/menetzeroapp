<?php

namespace App\Http\Controllers;

use App\Services\ContactInquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactInquiryController extends Controller
{
    public function storePublic(Request $request, ContactInquiryService $contacts)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'topic' => 'required|in:support,sales',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'source' => 'Public contact page',
        ];

        $result = $contacts->submit($data['topic'], $payload);

        if (!$result['ok']) {
            Log::warning('Public contact form failed', ['error' => $result['error']]);

            return back()
                ->withInput()
                ->with('error', 'We could not send your message. Please try again or email us directly at ' . site_support_email() . '.');
        }

        $thanks = $data['topic'] === 'sales'
            ? 'Thank you — our sales team will reply to ' . $data['email'] . ' shortly.'
            : 'Thank you — our support team will reply to ' . $data['email'] . ' shortly.';

        return redirect()
            ->route('contact')
            ->with('success', $thanks);
    }

    public function createPortal()
    {
        $consultant = Auth::guard('consultant')->user();
        $user = Auth::guard('web')->user();

        if (!$consultant && !$user) {
            return redirect()->route('login');
        }

        if ($consultant) {
            $defaults = [
                'name' => $consultant->name,
                'email' => $consultant->email,
                'company' => $consultant->company_name,
                'source' => 'Consultant portal — Help & Guide',
            ];
            $backRoute = 'consultant.help';
        } else {
            $activeCompany = $user->getActiveCompany();
            $defaults = [
                'name' => $user->name,
                'email' => $user->email,
                'company' => $activeCompany?->name,
                'source' => 'Company portal — Help & Guide',
            ];
            $backRoute = 'client.help';
        }

        return view('contact.portal-support', compact('defaults', 'backRoute', 'consultant'));
    }

    public function storePortal(Request $request, ContactInquiryService $contacts)
    {
        $consultant = Auth::guard('consultant')->user();
        $user = Auth::guard('web')->user();

        if (!$consultant && !$user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($consultant) {
            $payload = [
                'name' => $consultant->name,
                'email' => $consultant->email,
                'phone' => $consultant->phone,
                'company' => $consultant->company_name,
                'subject' => $data['subject'] ?? 'Consultant portal support',
                'message' => $data['message'],
                'source' => 'Consultant portal — Help & Guide',
            ];
            $backRoute = 'consultant.support';
        } else {
            $activeCompany = $user->getActiveCompany();
            $payload = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $activeCompany?->name,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'source' => 'Company portal — Help & Guide',
            ];
            $backRoute = 'client.support';
        }

        $result = $contacts->submit('support', $payload);

        if (!$result['ok']) {
            Log::warning('Portal support form failed', ['error' => $result['error']]);

            return back()
                ->withInput()
                ->with('error', 'We could not send your request. Please try again or email ' . site_support_email() . '.');
        }

        return redirect()
            ->route($backRoute)
            ->with('success', 'Your support request was sent to ' . site_support_email() . '. We will reply to ' . $payload['email'] . '.');
    }
}
