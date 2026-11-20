<?php

namespace App\Mail;

use App\Models\OilOrder;
use App\Support\OilOrders;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Récapitulatif envoyé à la personne qui réserve des bouteilles d'huile, avec son numéro de commande. */
class OilOrderMail extends Mailable
{
    public function __construct(public OilOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('services.contact.email'), 'Église Saint Joseph')],
            subject: 'Votre réservation d\'huile d\'olive n° '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.oil-order', with: ['settings' => OilOrders::settings()]);
    }
}
