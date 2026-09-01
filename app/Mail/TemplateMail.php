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

    /**
     * @param  list<array{data: string, name: string, mime?: string}>  $rawAttachments
     *         In-memory attachments (the invoice PDF). Raw data rather than a
     *         path so a queued job does not depend on the file still being
     *         there when it runs.
     */
    public function __construct(
        public EmailTemplate $template,
        public array $variables = [],
        public array $rawAttachments = [],
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

        foreach ($this->rawAttachments as $attachment) {
            if (empty($attachment['data']) || empty($attachment['name'])) {
                continue;
            }

            $mail->attachData(
                $attachment['data'],
                $attachment['name'],
                ['mime' => $attachment['mime'] ?? 'application/pdf'],
            );
        }

        return $this->applyGlobalBcc($mail);
    }
}
