<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::query()
            ->orderBy('mailer')
            ->orderBy('name')
            ->get()
            ->groupBy('mailer');

        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'addressLabels' => [
                'hello' => config('mail.addresses.hello.address'),
                'help' => config('mail.addresses.help.address'),
                'noreply' => config('mail.addresses.noreply.address'),
            ],
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'mailer' => 'required|in:hello,help,noreply',
            'reply_to' => 'nullable|in:hello,help,noreply',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['reply_to'] = $data['reply_to'] ?: null;

        $emailTemplate->update($data);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', 'Email template saved.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate, EmailTemplateService $mail)
    {
        $sample = [
            'user_name' => 'Sample User',
            'user_email' => 'user@example.com',
            'company_name' => 'Demo Trading LLC',
            'inviter_name' => Auth::user()?->name ?? 'Admin',
            'role_name' => 'Data entry',
            'invitation_url' => url('/invitations/accept/sample-token'),
            'expires_at' => now()->addDays(7)->format('F j, Y'),
            'reset_url' => url('/password/reset/sample'),
            'verify_url' => url('/email/verify/sample'),
            'dashboard_url' => route('client.dashboard'),
            'billing_url' => route('subscriptions.billing'),
            'plan_name' => 'Growth',
            'amount' => '4,999.00',
            'currency' => 'AED',
            'invoice_number' => 'INV-2026-001',
            'invoice_url' => url('/billing'),
            'paid_at' => now()->format('F j, Y g:i A'),
            'description' => 'Annual Growth subscription',
            'changed_at' => now()->format('F j, Y g:i A'),
            'alert_subject' => 'Payment webhook failure',
            'alert_message' => 'Sample system alert body.',
            'alert_context' => json_encode(['transaction_id' => 1], JSON_PRETTY_PRINT),
            'sender_name' => 'Sample Sender',
            'sender_email' => 'sender@example.com',
            'message_excerpt' => 'Sample contact message excerpt…',
        ];

        $html = view('emails.template', [
            'bodyHtml' => $emailTemplate->renderBodyHtml($sample),
            'previewText' => $emailTemplate->renderBodyText($sample),
        ])->render();

        return response($html);
    }
}
