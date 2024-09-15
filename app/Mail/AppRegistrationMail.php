<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class AppRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailBody;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailBody)
    {
        $this->mailBody = $mailBody;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('nimgori22@gmail.com', 'Help Circle')
                    ->subject('Welcome to ' . env('APP_NAME') . ' family.')
                    ->view('emails.app-registration-detail-mail')
                    ->with([
                        'mailBody' => $this->mailBody,
                    ]);
    }
}
