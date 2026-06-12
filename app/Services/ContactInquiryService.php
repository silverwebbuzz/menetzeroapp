<?php

namespace App\Services;

use App\Mail\ContactInboundMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactInquiryService
{
    public function __construct(
        protected EmailTemplateService $templates,
    ) {}

    /**
     * @param  array{name: string, email: string, message: string, phone?: string|null, subject?: string|null, source?: string|null, company?: string|null}  $data
     * @return array{ok: bool, error: ?string}
     */
    public function submit(string $type, array $data): array
    {
        $type = $type === 'sales' ? 'sales' : 'support';
        $mailbox = $type === 'sales' ? 'hello' : 'help';
        $inbox = config("mail.addresses.{$mailbox}.address");
        $typeLabel = $type === 'sales' ? 'Sales enquiry' : 'Support request';
        $ackSlug = $type === 'sales' ? 'contact_sales_ack' : 'contact_support_ack';

        // Deliver via shared SMTP (noreply credentials) — From address stays help@/hello@ in TemplateMail.
        $transport = mail_transport_for_mailbox($mailbox);
        $from = config('mail.addresses.noreply', config('mail.from'));

        $excerpt = Str::limit(strip_tags($data['message']), 280);

        $subject = $type === 'sales'
            ? '[Sales] ' . ($data['subject'] ?? 'Website enquiry') . ' — ' . $data['name']
            : '[Support] ' . ($data['subject'] ?? 'Help request') . ' — ' . $data['name'];

        try {
            Mail::mailer($transport)
                ->to($inbox)
                ->send(new ContactInboundMail($from, $subject, $data, $typeLabel));
        } catch (\Throwable $e) {
            Log::error('Contact form — inbox delivery failed', [
                'type' => $type,
                'inbox' => $inbox,
                'transport' => $transport,
                'submitter' => $data['email'] ?? null,
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }

        if (!$this->templates->send($ackSlug, $data['email'], [
            'sender_name' => $data['name'],
            'sender_email' => $data['email'],
            'message_excerpt' => $excerpt,
        ])) {
            Log::warning('Contact form — inbox delivered but auto-reply failed', [
                'type' => $type,
                'ack_slug' => $ackSlug,
                'to' => $data['email'],
            ]);
        }

        return ['ok' => true, 'error' => null];
    }
}
