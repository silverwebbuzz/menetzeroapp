<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $changedAt;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->changedAt = now();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Password Changed Successfully - ' . config('app.name', 'MENetZero'))
                    ->view('emails.password-changed');
    }
}

