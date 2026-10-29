<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkMail extends Mailable
{
    public function __construct(public User $user, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre lien de connexion — '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link', with: ['minutes' => MagicLink::MINUTES]);
    }
}
