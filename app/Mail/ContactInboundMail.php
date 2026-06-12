<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesGlobalBcc;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactInboundMail extends Mailable
{
    use AppliesGlobalBcc, Queueable, SerializesModels;

    /**
     * @param  array{address?: string, name?: string|null}  $fromAddress
     * @param  array<string, mixed>  $inquiry
     */
    public function __construct(
        public array $fromAddress,
        public string $subjectLine,
        public array $inquiry,
        public string $typeLabel,
    ) {}

    public function build(): self
    {
        $rows = [
            'Type' => $this->typeLabel,
            'Name' => $this->inquiry['name'] ?? '—',
            'Email' => $this->inquiry['email'] ?? '—',
        ];

        if (!empty($this->inquiry['phone'])) {
            $rows['Phone'] = $this->inquiry['phone'];
        }
        if (!empty($this->inquiry['subject'])) {
            $rows['Subject'] = $this->inquiry['subject'];
        }
        if (!empty($this->inquiry['source'])) {
            $rows['Source'] = $this->inquiry['source'];
        }
        if (!empty($this->inquiry['company'])) {
            $rows['Company / agency'] = $this->inquiry['company'];
        }

        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr><td style="padding:8px 12px;color:#64748b;vertical-align:top;">' . e($label) . '</td>'
                . '<td style="padding:8px 12px;"><strong>' . e((string) $value) . '</strong></td></tr>';
        }

        $message = nl2br(e($this->inquiry['message'] ?? ''));

        $html = <<<HTML
<p><strong>New contact form submission</strong></p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">{$table}</table>
<p style="margin:16px 0 8px;color:#64748b;font-size:13px;">Message</p>
<div style="background:#f8fafc;padding:16px;border-radius:8px;font-size:14px;line-height:1.6;">{$message}</div>
<p style="margin-top:16px;font-size:13px;color:#64748b;">Reply directly to {$this->inquiry['email']} to respond.</p>
HTML;

        return $this->applyGlobalBcc(
            $this->from(
                $this->fromAddress['address'] ?? config('mail.from.address'),
                $this->fromAddress['name'] ?? config('mail.from.name'),
            )
                ->replyTo($this->inquiry['email'] ?? null, $this->inquiry['name'] ?? null)
                ->subject($this->subjectLine)
                ->view('emails.template', [
                    'bodyHtml' => $html,
                    'previewText' => 'New ' . $this->typeLabel . ' inquiry',
                ])
        );
    }
}
