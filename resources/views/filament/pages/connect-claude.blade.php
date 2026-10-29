<x-filament-panels::page>
    <style>
        .cc-steps { counter-reset: step; display: grid; gap: 14px; }
        .cc-step { display: grid; grid-template-columns: 34px 1fr; gap: 14px; align-items: start; }
        .cc-step::before { counter-increment: step; content: counter(step); width: 34px; height: 34px; border-radius: 50%; background: #C5A24B; color: #00204E; font-weight: 700; display: grid; place-items: center; }
        .cc-copy { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin: 6px 0 16px; }
        .cc-url { font-family: ui-monospace, monospace; font-size: 1rem; padding: 6px 12px; border-radius: 8px; background: #F6F1E6; border: 1px solid #EDE5D3; user-select: all; }
        .cc-prompt { background: #F6F1E6; border-left: 3px solid #C5A24B; border-radius: 6px; padding: 10px 14px; margin: 8px 0; font-style: italic; }
        .cc-muted { color: #6b7280; font-size: .9rem; }
    </style>

    <x-filament::section>
        <x-slot name="heading">À quoi ça sert ?</x-slot>
        <div class="fi-prose">
            <p><strong>Claude</strong> est un assistant d'écriture (une « intelligence artificielle ») avec qui l'on discute comme par message.
                Une fois relié au site, vous pouvez lui confier un document et lui demander d'en faire un article :
                il le met en forme, ajoute les photos et le range dans l'admin, <strong>en brouillon</strong>.
                Vous n'avez plus qu'à relire et publier.</p>
            <p>Vous pouvez aussi lui donner l'adresse d'une page du site et lui demander de la modifier
                (« corrige les fautes », « ajoute un paragraphe sur… », « mets à jour le montant de la collecte »…).</p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Relier Claude au site (une seule fois, environ 3 minutes)</x-slot>
        <x-slot name="description">À faire sur un ordinateur. Il faut un compte Claude : le compte gratuit suffit.</x-slot>

        <div class="cc-steps">
            <div class="cc-step"><div class="fi-prose">
                <p>Ouvrez <a href="https://claude.ai" target="_blank" rel="noopener"><strong>claude.ai</strong></a> et connectez-vous
                    (ou créez un compte avec votre adresse email).</p>
            </div></div>

            <div class="cc-step"><div class="fi-prose">
                <p>Dans le menu à gauche, cliquez sur <strong>Personnaliser</strong> (<em>Customize</em>), puis sur <strong>Connecteurs</strong> (<em>Connectors</em>).</p>
            </div></div>

            <div class="cc-step"><div class="fi-prose">
                <p>Cliquez sur <strong>+ Ajouter</strong> (<em>+ Add</em>), puis sur <strong>Ajouter un connecteur personnalisé</strong> (<em>Add custom connector</em>).</p>
            </div></div>

            <div class="cc-step"><div class="fi-prose">
                <p>Remplissez les deux cases :</p>
                <p>Nom : <strong>Site Saint Joseph</strong></p>
                <div>Adresse (URL) :</div>
                {{-- Copie de l'adresse ; repli par sélection du texte si le navigateur refuse l'accès au presse-papiers. --}}
                <div class="cc-copy fi-not-prose" x-data="{
                    copied: false,
                    async copy() {
                        const text = this.$refs.url.textContent.trim();
                        try {
                            await navigator.clipboard.writeText(text);
                        } catch (e) {
                            const range = document.createRange();
                            range.selectNodeContents(this.$refs.url);
                            getSelection().removeAllRanges();
                            getSelection().addRange(range);
                            document.execCommand('copy');
                        }
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    },
                }">
                    <span class="cc-url" x-ref="url">{{ $mcpUrl }}</span>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document"
                        x-on:click="copy()">
                        <span x-text="copied ? 'Copié !' : 'Copier'">Copier</span>
                    </x-filament::button>
                </div>
                <p>Cliquez sur <strong>Continuer</strong>. Laissez les réglages proposés tels quels et choisissez
                    <strong>Se connecter maintenant</strong> (<em>Sign in now</em>) s'il vous est demandé comment s'identifier, puis <strong>Ajouter</strong>.</p>
            </div></div>

            <div class="cc-step"><div class="fi-prose">
                <p>Une page du site Saint Joseph s'ouvre : « <strong>Autoriser Claude ?</strong> ». Cliquez sur <strong>Autoriser</strong>.</p>
                <p class="cc-muted">Si le site vous demande votre adresse email, saisissez-la : vous recevrez un lien de connexion,
                    comme pour l'admin. Ouvrez-le dans le même navigateur, l'autorisation reprend toute seule.</p>
            </div></div>

            <div class="cc-step"><div class="fi-prose">
                <p>C'est fait ! « Site Saint Joseph » apparaît dans la liste des connecteurs de Claude.</p>
            </div></div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Ensuite, à chaque fois</x-slot>
        <div class="fi-prose">
            <p>Dans une nouvelle conversation sur claude.ai, cliquez sur le bouton <strong>+</strong> en bas à gauche de la zone de saisie,
                puis <strong>Connecteurs</strong>, et vérifiez que <strong>Site Saint Joseph</strong> est activé.
                Joignez votre document (bouton <strong>+</strong> > ajouter un fichier, ou glissez-le), puis écrivez votre demande. Par exemple :</p>
            <div class="cc-prompt">Fais un article pour le site Saint Joseph à partir de ce document, avec ses photos.</div>
            <div class="cc-prompt">Sur la page https://saint-joseph.saint-arnoux.fr/le-projet/budget/le-budget-du-projet, mets à jour les montants avec ceux du document joint.</div>
            <div class="cc-prompt">Mets à jour le montant de la collecte sur la page d'accueil : 1 550 000 €.</div>

            <h3>Les photos</h3>
            <p>Claude prend les photos dans la <a href="{{ $libraryUrl }}"><strong>Bibliothèque d'images</strong></a> du site.
                <strong>Avant de lui demander un article, ajoutez-y vos photos</strong> (ou directement votre document Word : ses images en sont extraites).
                Pas d'inquiétude pour les doublons : une image déjà présente n'est pas ajoutée deux fois.</p>
            <p>Vous pouvez ensuite lui indiquer lesquelles utiliser (« prends les photos de la bénédiction »), ou le laisser choisir parmi les dernières ajoutées.
                S'il manque des images, Claude vous demandera de les ajouter à la bibliothèque.</p>
            <div class="cc-prompt">J'ai ajouté les photos du pèlerinage dans la bibliothèque : fais un article à partir de ce compte rendu, avec la photo du groupe en couverture.</div>

            <h3>Bon à savoir</h3>
            <ul>
                <li>Les nouveaux articles sont créés <strong>en brouillon</strong> : Claude vous donne le lien pour les relire et les publier dans l'admin.</li>
                <li>Les modifications d'une page déjà en ligne sont visibles tout de suite : demandez à Claude de vous montrer le texte avant s'il s'agit d'un changement important.</li>
                <li>Claude peut se tromper : relisez toujours, surtout les noms, les dates et les montants.</li>
                <li>Claude ne peut rien supprimer sur le site.</li>
            </ul>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Mes connexions</x-slot>
        <x-slot name="description">Applications Claude ayant accès au site en votre nom. « Déconnecter » coupe l'accès immédiatement (il faudra refaire l'étape 5 pour le rétablir).</x-slot>

        @forelse ($connections as $connection)
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 0; border-top:1px solid #f1f1f1;">
                <div>
                    <strong>{{ $connection['name'] }}</strong>
                    <div class="cc-muted">Connecté depuis le {{ $connection['since']->translatedFormat('j F Y') }} · dernière activité le {{ $connection['last']->translatedFormat('j F Y') }}</div>
                </div>
                <x-filament::button color="danger" size="sm" outlined
                    wire:click="disconnect('{{ $connection['client_id'] }}')"
                    wire:confirm="Couper l'accès de {{ $connection['name'] }} au site ?">
                    Déconnecter
                </x-filament::button>
            </div>
        @empty
            <p class="cc-muted">Aucune connexion pour l'instant.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
