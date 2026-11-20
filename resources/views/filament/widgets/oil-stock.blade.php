@use('App\Support\OilOrders')
<x-filament-widgets::widget>
    <style>
        .oil-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
        .oil-tile { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 12px 14px; min-width: 0; }
        .dark .oil-tile { background: rgb(255 255 255 / .05); border-color: rgb(255 255 255 / .1); }
        .oil-tile h3 { font-weight: 600; color: #6b7280; font-size: .9rem; }
        .oil-tile b { display: block; font-size: 1.9rem; line-height: 1.1; color: #00204E; font-variant-numeric: tabular-nums; }
        .dark .oil-tile b { color: #fff; }
        .oil-tile .oil-after { color: #374151; font-size: .85rem; }
        .dark .oil-tile .oil-after { color: #d1d5db; }
        .oil-tile .oil-after.out { color: #b91c1c; }
        .oil-tile span { color: #6b7280; font-size: .85rem; }
        .oil-tile small { display: block; color: #9a7b2c; font-size: .8rem; margin-top: 4px; }
        /* Tableau des commandes : colonnes de bouteilles étroites (« 250 / mL » sur deux lignes), pour tenir sur un téléphone. */
        .fi-ta-header-cell[class*="-bottles"], .fi-ta-cell[class*="-bottles"] { padding-inline: 4px; width: 1%; }
        .fi-ta-cell[class*="-bottles"] .fi-ta-text { padding-inline: 4px; justify-content: center; }
        @media (max-width: 639px) {
            .fi-ta-header-cell-last-name, .fi-ta-header-cell-status { padding-inline: 8px 4px; }
            .fi-ta-cell-last-name .fi-ta-text, .fi-ta-cell-status .fi-ta-text { padding-inline: 8px 4px; }
        }
        .oil-closed { border-radius: 10px; padding: 8px 14px; border: 1px solid #e5e7eb; background: #f3f4f6; color: #374151; margin-bottom: 12px; }
    </style>
    @unless ($open)
        <div class="oil-closed"><strong>Réservations closes.</strong> La page /commande-huile ne prend plus de commande (bouton « Réglages » pour les rouvrir).</div>
    @endunless
    <div class="oil-tiles">
        @foreach ($rows as $row)
            <div class="oil-tile">
                <h3>{{ $row['product']->label }} · {{ OilOrders::money($row['product']->price_cents) }}</h3>
                <b>{{ $row['on_hand'] }}</b>
                <span>en stock</span>
                <small>{{ $row['collected'] }} retirée{{ $row['collected'] > 1 ? 's' : '' }} · {{ $row['reserved'] }} à retirer</small>
                <small @class(['oil-after', 'out' => $row['stock'] === 0])>Après les commandes&nbsp;: <strong>{{ $row['stock'] }}</strong>{{ $row['stock'] === 0 ? ' (épuisé)' : '' }}</small>
            </div>
        @endforeach
        <div class="oil-tile">
            <h3>Commandes</h3>
            <b>{{ $orders }}</b>
            <span>{{ OilOrders::money($amount) }} réservés</span>
            <small>{{ $toCollect }} à retirer</small>
        </div>
    </div>
</x-filament-widgets::widget>
