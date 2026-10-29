<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    /** @param array{name: string, email: string, subject: ?string, message: string} $data */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->data['email'], $this->data['name'])],
            subject: '[Site Saint Joseph] '.($this->data['subject'] ?: 'Nouveau message de '.$this->data['name']),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact');
    }
}
