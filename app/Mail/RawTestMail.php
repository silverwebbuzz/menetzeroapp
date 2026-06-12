<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RawTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{address?: string, name?: string|null}  $fromAddress
     */
    public function __construct(
        public array $fromAddress,
        public string $htmlBody,
        public string $subjectLine,
    ) {}

    public function build(): self
    {
        return $this->from(
            $this->fromAddress['address'] ?? config('mail.from.address'),
            $this->fromAddress['name'] ?? config('mail.from.name'),
        )
            ->subject($this->subjectLine)
            ->view('emails.template', [
                'bodyHtml' => $this->htmlBody,
                'previewText' => 'MeNetZero raw email test',
            ]);
    }
}
