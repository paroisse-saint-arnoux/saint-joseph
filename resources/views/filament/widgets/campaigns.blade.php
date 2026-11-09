@php($n = fn ($v) => number_format((int) $v, 0, ',', ' '))
<x-filament-widgets::widget>
    <x-filament::section heading="Campagnes (30 derniers jours)" description="Visites arrivées par un lien marqué (emails Brevo, liens de la Croix du Pape…). Pour les emails, activer le suivi « Google Analytics » dans les réglages de la campagne Brevo.">
        @if (! $configured)
            <p style="color:#6b7280; font-size:.875rem;">Statistiques non configurées : ajoutez MATOMO_TOKEN dans le fichier .env du serveur.</p>
        @elseif (empty($campaigns))
            <p style="color:#6b7280; font-size:.875rem;">Aucune visite venue d'une campagne sur la période.</p>
        @else
            @unless ($sourcesAvailable)
                <p style="color:#6b7280; font-size:.8rem; margin-bottom:8px;">Pour voir les sources (utm_source) de chaque campagne, installer l'extension gratuite « Marketing Campaigns Reporting » dans Matomo.</p>
            @endunless
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                    <thead>
                        <tr style="color:#6b7280; text-align:left;">
                            <th style="padding:6px 8px; font-weight:600;">Campagne</th>
                            <th style="padding:6px 8px; font-weight:600; text-align:right;">Visites</th>
                            <th style="padding:6px 8px; font-weight:600; text-align:right;">Pages vues</th>
                            <th style="padding:6px 8px; font-weight:600;">Email Brevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaigns as $c)
                            <tr style="border-top:1px solid #f1f1f1;">
                                <td style="padding:8px;">
                                    <strong>{{ $c['label'] }}</strong>
                                    @if ($c['sources'])
                                        <div style="color:#6b7280; font-size:.8rem;">Sources : {{ collect($c['sources'])->map(fn ($v, $k) => $k.' ('.$v.')')->join(' · ') }}</div>
                                    @endif
                                </td>
                                <td style="padding:8px; text-align:right; font-variant-numeric:tabular-nums;"><strong>{{ $n($c['visits']) }}</strong></td>
                                <td style="padding:8px; text-align:right; font-variant-numeric:tabular-nums;">{{ $n($c['actions']) }}</td>
                                <td style="padding:8px; color:#4b5563;">
                                    @if ($b = $c['brevo'])
                                        {{ $b['sent_at']?->translatedFormat('j M') }} · {{ $n($b['delivered']) }} délivrés · {{ $n($b['opens']) }} ouvertures · {{ $n($b['clicks']) }} clics
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
