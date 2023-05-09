<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class CustomerPasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */public $data;
    public function __construct($data)
    {
        $this->data = $data;
        //
    }

    public function build()
    {
        return $this->markdown('emails.customer_password_reset_link');
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('surfieethiopia@gmail.com', 'Surfie Ethiopia'),
            subject: 'Reset Password!',
        );
    }
    /**
     * Get the message content definition.
     */

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}