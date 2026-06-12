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
     */
    public function submit(string $type, array $data): bool
    {
        $type = $type === 'sales' ? 'sales' : 'support';
        $mailbox = $type === 'sales' ? 'hello' : 'help';
        $transport = $type === 'sales' ? 'smtp_hello' : 'smtp_help';
        $inbox = config("mail.addresses.{$mailbox}.address");
        $from = config("mail.addresses.{$mailbox}", config('mail.from'));
        $typeLabel = $type === 'sales' ? 'Sales enquiry' : 'Support request';
        $ackSlug = $type === 'sales' ? 'contact_sales_ack' : 'contact_support_ack';

        $excerpt = Str::limit(strip_tags($data['message']), 280);

        try {
            $subject = $type === 'sales'
                ? '[Sales] ' . ($data['subject'] ?? 'Website enquiry') . ' — ' . $data['name']
                : '[Support] ' . ($data['subject'] ?? 'Help request') . ' — ' . $data['name'];

            Mail::mailer($transport)
                ->to($inbox)
                ->send(new ContactInboundMail($from, $subject, $data, $typeLabel));

            $this->templates->send($ackSlug, $data['email'], [
                'sender_name' => $data['name'],
                'sender_email' => $data['email'],
                'message_excerpt' => $excerpt,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Contact form submission failed', [
                'type' => $type,
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
