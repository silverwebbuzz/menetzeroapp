<?php

namespace App\Mail\Concerns;

use App\Models\SiteSetting;

trait AppliesGlobalBcc
{
    public static function globalBccAddress(): ?string
    {
        $bcc = trim((string) SiteSetting::get('mail_global_bcc', config('emails.global_bcc', '')));

        return filter_var($bcc, FILTER_VALIDATE_EMAIL) ? $bcc : null;
    }

    /**
     * @param  \Illuminate\Mail\Mailable|\Illuminate\Mail\Mailables\Envelope  $mail
     */
    protected function applyGlobalBcc(mixed $mail): mixed
    {
        if ($bcc = static::globalBccAddress()) {
            $mail->bcc($bcc);
        }

        return $mail;
    }
}
