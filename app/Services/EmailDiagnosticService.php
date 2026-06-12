<?php

namespace App\Services;

use App\Mail\RawTestMail;
use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailDiagnosticService
{
    public function __construct(
        protected EmailTemplateService $templates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function configSnapshot(): array
    {
        $default = config('mail.default');
        $mailboxes = [];

        foreach (['hello', 'help', 'noreply'] as $key) {
            $transportKey = $this->transportMailer($key);
            $mailboxes[$key] = [
                'address' => config("mail.addresses.{$key}.address"),
                'name' => config("mail.addresses.{$key}.name"),
                'transport_mailer' => $transportKey,
                'transport' => $this->transportConfig($transportKey),
            ];
        }

        return [
            'default_mailer' => $default,
            'ehlo_domain' => config('mail.mailers.smtp.local_domain'),
            'alert_to' => config('emails.alert_to'),
            'mailboxes' => $mailboxes,
            'warnings' => $this->configWarnings($default, $mailboxes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendRawTest(string $mailbox, string $to, ?string $subject = null): array
    {
        $started = microtime(true);
        $transportKey = $this->transportMailer($mailbox);
        $from = config("mail.addresses.{$mailbox}", config('mail.from'));
        $subject = $subject ?: 'MeNetZero email test — ' . now()->format('Y-m-d H:i:s');

        $body = <<<HTML
<p>This is a <strong>raw SMTP test</strong> from the MeNetZero admin email tester.</p>
<p>Mailbox: <code>{$mailbox}</code><br>
Transport: <code>{$transportKey}</code><br>
Sent at: {$subject}</p>
<p>If you received this, outbound mail for this mailbox is working.</p>
HTML;

        $details = [
            'mode' => 'raw',
            'mailbox' => $mailbox,
            'transport_mailer' => $transportKey,
            'transport' => $this->transportConfig($transportKey),
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
        ];

        try {
            Mail::mailer($transportKey)
                ->to($to)
                ->send(new RawTestMail($from, $body, $subject));

            $details['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

            return $this->success("Raw test email accepted by the mail transport for {$to}.", $details);
        } catch (\Throwable $e) {
            return $this->failure($e, $details, $started);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sendTemplateTest(string $slug, string $to): array
    {
        $started = microtime(true);
        $template = $this->templates->resolve($slug);

        if (!$template) {
            return [
                'ok' => false,
                'summary' => "Template “{$slug}” was not found or is inactive.",
                'error' => 'Unknown template slug.',
                'error_class' => null,
                'error_trace' => null,
                'details' => ['mode' => 'template', 'slug' => $slug, 'to' => $to],
                'config_warnings' => [],
            ];
        }

        $transportKey = $this->transportMailer($template->mailer);
        $variables = $this->sampleVariables();
        $from = config("mail.addresses.{$template->mailer}", config('mail.from'));

        $details = [
            'mode' => 'template',
            'slug' => $slug,
            'template_name' => $template->name,
            'mailbox' => $template->mailer,
            'transport_mailer' => $transportKey,
            'transport' => $this->transportConfig($transportKey),
            'from' => $from,
            'to' => $to,
            'subject' => $template->renderSubject($variables),
        ];

        try {
            $mailable = (new TemplateMail($template, $variables))->mailer($transportKey);
            $pending = Mail::mailer($transportKey)->to($to);

            if ($replyKey = $template->reply_to) {
                $reply = config("mail.addresses.{$replyKey}");
                if (!empty($reply['address'])) {
                    $pending->replyTo($reply['address'], $reply['name'] ?? null);
                }
            }

            $pending->send($mailable);

            $details['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

            return $this->success("Template “{$template->name}” was accepted by the mail transport for {$to}.", $details);
        } catch (\Throwable $e) {
            return $this->failure($e, $details, $started);
        }
    }

    /**
     * @return array<string, string>
     */
    public function sampleVariables(): array
    {
        return [
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'company_name' => 'Demo Trading LLC',
            'inviter_name' => 'Admin',
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
            'invoice_number' => 'INV-TEST-001',
            'invoice_url' => url('/billing'),
            'paid_at' => now()->format('F j, Y g:i A'),
            'description' => 'Email tester — sample subscription',
            'changed_at' => now()->format('F j, Y g:i A'),
            'alert_subject' => 'Test system alert',
            'alert_message' => 'This is a sample alert from the email tester.',
            'alert_context' => json_encode(['source' => 'admin_email_tester'], JSON_PRETTY_PRINT),
            'sender_name' => 'Test Sender',
            'sender_email' => 'sender@example.com',
            'message_excerpt' => 'Sample contact message…',
        ];
    }

    protected function transportMailer(string $mailbox): string
    {
        return match ($mailbox) {
            'hello' => 'smtp_hello',
            'help' => 'smtp_help',
            'noreply' => 'smtp_noreply',
            default => config('mail.default', 'log'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function transportConfig(string $transportKey): array
    {
        $cfg = config("mail.mailers.{$transportKey}", config('mail.mailers.' . config('mail.default')));

        return [
            'transport' => $cfg['transport'] ?? 'unknown',
            'host' => $cfg['host'] ?? '—',
            'port' => $cfg['port'] ?? '—',
            'scheme' => $cfg['scheme'] ?? ($cfg['encryption'] ?? '—'),
            'username' => $cfg['username'] ?? '—',
            'password_set' => !empty($cfg['password']),
            'local_domain' => $cfg['local_domain'] ?? '—',
        ];
    }

    /**
     * @param  array<string, mixed>  $mailboxes
     * @return list<string>
     */
    protected function configWarnings(string $default, array $mailboxes): array
    {
        $warnings = [];

        if ($default === 'log') {
            $warnings[] = 'MAIL_MAILER is set to log — messages are written to storage/logs/laravel.log and are NOT sent over SMTP.';
        }

        if ($default === 'array') {
            $warnings[] = 'MAIL_MAILER is array — messages are stored in memory only (tests).';
        }

        foreach ($mailboxes as $key => $box) {
            $t = $box['transport'] ?? [];
            if (($t['transport'] ?? '') === 'smtp') {
                if (empty($t['host']) || $t['host'] === '127.0.0.1') {
                    $warnings[] = "Mailbox “{$key}”: SMTP host looks unset (127.0.0.1). Set MAIL_HOST or MAIL_" . strtoupper($key) . "_HOST on production.";
                }
                if (empty($t['username']) || $t['username'] === '—') {
                    $warnings[] = "Mailbox “{$key}”: SMTP username is empty.";
                }
                if (empty($t['password_set'])) {
                    $warnings[] = "Mailbox “{$key}”: SMTP password is empty.";
                }
            }
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function success(string $summary, array $details): array
    {
        return [
            'ok' => true,
            'summary' => $summary,
            'error' => null,
            'error_class' => null,
            'error_trace' => null,
            'transport_hint' => $this->transportHint(null),
            'details' => $details,
            'config_warnings' => $this->configSnapshot()['warnings'],
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function failure(\Throwable $e, array $details, float $started): array
    {
        $details['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return [
            'ok' => false,
            'summary' => 'Send failed — see error details below.',
            'error' => $e->getMessage(),
            'error_class' => $e::class,
            'error_trace' => $e->getTraceAsString(),
            'transport_hint' => $this->transportHint($e),
            'details' => $details,
            'config_warnings' => $this->configSnapshot()['warnings'],
        ];
    }

    protected function transportHint(?\Throwable $e): ?string
    {
        if (!$e) {
            return 'SMTP accepted the message. If the inbox is empty, check spam, DNS (SPF/DKIM), and that the recipient address exists.';
        }

        $msg = strtolower($e->getMessage());

        if ($e instanceof TransportExceptionInterface || str_contains($msg, 'connection') || str_contains($msg, 'could not connect')) {
            return 'Cannot reach the SMTP server. Check MAIL_HOST, MAIL_PORT, firewall, and that mail.menetzero.com resolves correctly.';
        }

        if (str_contains($msg, 'authentication') || str_contains($msg, '535') || str_contains($msg, 'username and password')) {
            return 'SMTP authentication failed. Verify the mailbox password and that MAIL_USERNAME matches the From address (e.g. noreply@menetzero.com).';
        }

        if (str_contains($msg, '550') || str_contains($msg, 'does not exist')) {
            return 'Recipient rejected — the destination mailbox may not exist (typo?) or the server refused relay.';
        }

        return 'Review the error message and transport settings below. After changing .env run: php artisan config:clear';
    }
}
