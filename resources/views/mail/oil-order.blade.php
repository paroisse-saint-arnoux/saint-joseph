@use('App\Support\OilOrders')
<x-mail::message>
# Merci {{ $order->first_name }}&nbsp;!

Votre réservation d'huile d'olive est bien enregistrée. Gardez précieusement votre numéro de commande&nbsp;:

<x-mail::panel>
**Commande n° {{ $order->number }}**
</x-mail::panel>

<x-mail::table>
| Bouteille | Quantité | Prix |
|:--|:-:|--:|
@foreach ($order->items as $item)
| {{ $item->label }} ({{ OilOrders::money($item->unit_price_cents) }}) | {{ $item->quantity }} | {{ OilOrders::money($item->totalCents()) }} |
@endforeach
@if ($order->discounted)
| Tarif réduit (−{{ OilOrders::DISCOUNT_PERCENT }}&nbsp;%) | | −{{ OilOrders::money($order->fullPriceCents() - $order->total_cents) }} |
@endif
| **Total** | **{{ $order->bottles() }}** | **{{ $order->total() }}** |
</x-mail::table>

**Vos bouteilles sont à retirer {{ $settings['pickup'] }}.** {{ $settings['payment'] }} Il suffira de donner votre nom ou votre numéro de commande.

C'est un beau cadeau de Noël&nbsp;: chaque bouteille porte un QR code qui raconte la récolte de cette huile et le projet de construction de l'église Saint Joseph et de son centre paroissial, au Rouret.

<x-mail::button :url="route('huile')">
Découvrir la récolte
</x-mail::button>

Une question, un changement&nbsp;? Répondez simplement à cet e-mail.

Merci pour votre soutien,<br>
Paroisse Saint-Arnoux · Église Saint Joseph

<x-slot:subcopy>
Récapitulatif&nbsp;: {{ $order->summary() }} — {{ $order->fullName() }}, {{ $order->phoneForDisplay() }}.
</x-slot:subcopy>
</x-mail::message>
