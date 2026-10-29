<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkMail extends Mailable
{
    public function __construct(public User $user, public string $url, public bool $invitation = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->invitation
            ? 'Votre accès à l\'administration — '.config('app.name')
            : 'Votre lien de connexion — '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link', with: [
            'validity' => $this->invitation ? MagicLink::INVITATION_DAYS.' jours' : MagicLink::MINUTES.' minutes',
        ]);
    }
}
