<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesGlobalBcc;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable
{
    use AppliesGlobalBcc, Queueable, SerializesModels;

    public function __construct(
        public EmailTemplate $template,
        public array $variables = [],
    ) {}

    public function build(): self
    {
        $htmlBody = $this->template->renderBodyHtml($this->variables);
        $textBody = $this->template->renderBodyText($this->variables);
        $fromKey = $this->template->mailer ?: 'noreply';
        $from = config("mail.addresses.{$fromKey}", config('mail.from'));

        $mail = $this->from($from['address'] ?? config('mail.from.address'), $from['name'] ?? config('mail.from.name'))
            ->subject($this->template->renderSubject($this->variables))
            ->view('emails.template', [
                'bodyHtml' => $htmlBody,
                'previewText' => $textBody,
            ]);

        if ($replyKey = $this->template->reply_to) {
            $reply = config("mail.addresses.{$replyKey}");
            if (!empty($reply['address'])) {
                $mail->replyTo($reply['address'], $reply['name'] ?? null);
            }
        }

        if ($textBody) {
            $mail->text('emails.template-text', [
                'bodyText' => $textBody,
            ]);
        }

        return $this->applyGlobalBcc($mail);
    }
}
