<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailDiagnosticService;
use Illuminate\Http\Request;

class EmailTestController extends Controller
{
    public function index(EmailDiagnosticService $diagnostics)
    {
        $templates = EmailTemplate::query()
            ->where('is_active', true)
            ->orderBy('mailer')
            ->orderBy('name')
            ->get();

        if ($templates->isEmpty()) {
            $templates = collect(config('emails.templates', []))
                ->map(fn (array $def, string $slug) => (object) [
                    'slug' => $slug,
                    'name' => $def['name'] ?? $slug,
                    'mailer' => $def['mailer'] ?? 'noreply',
                ]);
        }

        return view('admin.email-test.index', [
            'config' => $diagnostics->configSnapshot(),
            'templates' => $templates,
            'result' => session('email_test_result'),
            'old' => session('_old_input', []),
        ]);
    }

    public function send(Request $request, EmailDiagnosticService $diagnostics)
    {
        $validated = $request->validate([
            'mode' => 'required|in:raw,template',
            'to' => 'required|email|max:255',
            'mailbox' => 'required_if:mode,raw|in:hello,help,noreply',
            'template_slug' => 'required_if:mode,template|string|max:100',
        ]);

        if ($validated['mode'] === 'raw') {
            $result = $diagnostics->sendRawTest($validated['mailbox'], $validated['to']);
        } else {
            $result = $diagnostics->sendTemplateTest($validated['template_slug'], $validated['to']);
        }

        return redirect()
            ->route('admin.email-test.index')
            ->withInput($request->only('mode', 'to', 'mailbox', 'template_slug'))
            ->with('email_test_result', $result);
    }
}
