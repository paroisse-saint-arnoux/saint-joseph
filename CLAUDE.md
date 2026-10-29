# CLAUDE.md — Site Saint Joseph

Site du projet de centre paroissial et d'église Saint Joseph (Paroisse Saint-Arnoux, Le Rouret).
Production : https://saint-joseph.saint-arnoux.fr/ — hébergement mutualisé **O2Switch** (LAMP, PHP 8.5, MySQL, accès SSH depuis les IP autorisées dans cPanel, déploiement par `git` côté serveur).

**« SJO2 » est un code interne : il ne doit jamais apparaître sur le site** (textes, meta, données structurées, images, emails). Le nom public est « Église Saint Joseph » / « Centre paroissial et église Saint Joseph », Paroisse Saint-Arnoux, Le Rouret.

Ce fichier consigne les décisions prises avec Bertrand Gorge. **Le tenir à jour** à chaque nouvelle décision.

## Stack

- **Laravel 13** + **Filament 5** (admin en français, `/admin`), Livewire 4, **Passport** (OAuth du connecteur Claude + jetons API), `laravel/mcp` (serveur MCP).
- Front : Blade + **SCSS + Bootstrap 5.3** (import sélectif, pas de Tailwind), Vite, JS vanilla en modules.
- Polices Fraunces / Hanken Grotesk auto-hébergées (`@fontsource-variable`, pas de Google Fonts).
- Images : `intervention/image` (GD) — tout upload est redimensionné (1920 px max) et converti en **WebP** (`App\Support\Images`).
- Traductions françaises de Laravel : fichiers versionnés dans `lang/` (`fr.json`, `lang/fr/*.php`). Le paquet `laravel-lang/common` qui les a générés a été **retiré** (il dépend de paquets `dragon-code/*` abandonnés, d'où des avertissements de Composer au déploiement). Pour les mettre à jour : `composer require --dev laravel-lang/common`, `php artisan lang:update`, puis `composer remove --dev laravel-lang/common`. Filament a ses propres traductions.
- Markdown : `league/commonmark` GFM, HTML brut autorisé (éditeurs de confiance), iframes autorisées ; un lien YouTube seul sur sa ligne devient une vidéo intégrée (`App\Support\Markdown`).

## Développement (Docker)

```bash
docker compose up -d                       # app (PHP 8.5/Apache) :8080, MySQL :3307, Mailpit :8025, DbGate :3000
docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app php artisan test
npm install && npm run dev                 # ou : docker compose --profile front up node
npm run build                              # build de production dans public/build
```

- La machine hôte n'a pas toutes les extensions PHP : **lancer PHP/Composer/artisan dans le conteneur** (`-u www-data`, `-e HOME=/tmp` pour Composer).
- Emails en local : Mailpit http://localhost:8025 (liens magiques, formulaire de contact).
- Base de données en local : DbGate http://localhost:3000 (connexion « SJO2 (MySQL locale) » préconfigurée).
- Admin local : http://localhost:8080/admin — saisir l'email `ADMIN_EMAIL` du `.env`, le lien arrive dans Mailpit.
- Lien de connexion direct, sans email (tests de rendu, captures Playwright) : `docker compose exec -u www-data app php artisan admin:lien [email]` (désactivé en production).
- `legacy/` = ancien one-pager statique, gardé comme référence de style/contenu. Non déployé. Pourra être supprimé.

## Décisions fonctionnelles

### Navigation
- En-tête : **Découvrir** (`/#decouvrir`), **Soutenir** (`/#collecte`), **Le projet** (`/le-projet`), **Faire un don** (lien externe `site.don_url`, plateforme du diocèse).
- Pied de page : réseaux sociaux (Facebook, LinkedIn), Qui sommes-nous (→ article de la catégorie Équipe), Contact (`/contact`), liens utiles, mentions légales, confidentialité.
- Sur **chaque page** : bandeau newsletter + don (`partials/engage.blade.php`).

### Page d'accueil (tout est éditable dans Admin > Page d'accueil, clé `home` de la table `settings`)
Hero → Découvrir (intro + bouton vers `/le-projet`) → Le projet (chiffres clés, bâtiments, visuels) → Avancement (texte + frise d'étapes done/now/todo) → Collecte (« clocher qui monte », montant + objectif) → 3 derniers articles.
Supprimés de l'ancien site : architecture IA, valeurs/mission, parrains, simulateur de don, rampe 6 M€, bloc don/fiscalité, recrutement, « Restons en lien », calendrier.
**Pas de calendrier/agenda d'événements pour l'instant** (décision du 29/10/2026).

### Articles
- URL : `/le-projet` (tous), `/le-projet/{categorie}` (filtre), `/le-projet/{categorie}/{slug}`. Si la catégorie change, l'ancienne URL redirige en 301.
- **Une seule catégorie** par article, liste fixe (`App\Enums\Category`) : Agenda, Architecture, Budget, Construction, Projet, Présentation, Équipe.
- Tri : épinglés d'abord, puis antichronologique (`published_at`). **Pas de recherche**, filtre par catégorie, **défilement infini** (9 par page, JSON quand requête AJAX, lien « Voir plus » sans JS).
- Champs : titre, slug, intro (excerpt), image de partage (OG) + alt + `show_cover` (afficher ou non cette image en haut de l'article, oui par défaut ; elle sert toujours aux partages et aux cartes), corps, galerie (carrousel, alt obligatoire), statut, date de publication modifiable (date future = programmé), épinglé.
- **Deux formats** : `markdown` (édité dans l'admin, onglets Rédaction/Aperçu) et `html` (mis en page, généré par IA, ex. article Équipe ; éditeur de code HTML dans l'admin, écrit surtout via l'API).
- **Brouillons** : absents des listes et du sitemap, `noindex`, accessibles seulement via `?apercu={preview_token}` (ou si connecté à l'admin).
- Auteur (`author_id`, rempli automatiquement à la création, y compris via l'API) : **affiché uniquement dans l'admin**. Sur le site : pas d'auteur, pas de temps de lecture. Articles liés = même catégorie.
- Classes CSS disponibles pour les articles HTML : `.moa`, `.comp-grid/.comp`, `.benev`, `.team-grid/.member` (avec `.member-role`/`.member-bio` cachés → modale au clic), `.team-verse`, plus tout Bootstrap (grid, `.ratio`…).

### Admin & accès
- Connexion **par lien magique uniquement** (pas de mot de passe) : `App\Filament\Pages\Auth\Login` + `App\Support\MagicLink`. Lien à usage unique, 30 min, signé ; le lien mène à une page qui s'auto-soumet en JS (POST) puis redirige vers l'admin : l'utilisateur arrive directement connecté, et les antivirus des messageries (qui ouvrent les liens sans exécuter le JS) ne consomment pas le lien. Bouton de secours dans `<noscript>`.
- **Connexion sans expiration** : cookie « se souvenir de moi » de 400 jours (plafond des navigateurs), renouvelé à chaque visite de l'admin (`App\Http\Middleware\KeepAdminLoggedIn`). Se déconnecter invalide le cookie sur tous les appareils.
- **Tous les formulaires de l'admin** (articles, page d'accueil, réglages, fiches d'images, utilisateurs) : boutons Enregistrer / Annuler **à droite**, barre fixée en bas de l'écran **seulement dès qu'il y a des modifications non enregistrées** (`Page::stickyFormActions()` + `Page::alignFormActionsEnd()` dans `AdminPanelProvider` ; classe `sj-form-dirty` sur `<body>`, calculée comme l'alerte de Filament : empreinte de `$wire.data` comparée à `savedDataHash`, dans `resources/views/filament/scripts.blade.php`). Pages de réglages (`SettingsPage`) : trait `HasUnsavedDataChangesAlert`, empreinte recalculée après rechargement du formulaire (`fillFromSettings`), bouton « Annuler » qui revient au contenu enregistré. Alerte si l'on quitte une page avec des modifications non enregistrées (`unsavedChangesAlerts()`).
- Statut d'un article : **interrupteur** Brouillon (orange) / Publié (vert), champ virtuel `is_published` converti en `ArticleStatus` à l'enregistrement (`ArticleForm::applyStatus`), avec à sa droite « Voir sur le site » / « Prévisualiser » (en modification seulement). En bas du formulaire : « Supprimer » à gauche, « Annuler » / « Sauvegarder » à droite ; pas de « Créer & ajouter un autre ».
- Liste des articles : clic sur la ligne = édition ; la page publique s'ouvre seulement via « Voir » (l'action ne doit pas s'appeler `view`, sinon Filament en fait l'action du clic). Colonnes Auteur et « Modifié le ».
- Liste blanche = table `users` (champ `is_active`). Rôles : **admin** (gère les utilisateurs) / **editor** (contenu). Les éditeurs publient directement.
- **Création d'un utilisateur** (actif) dans l'admin → email d'accueil automatique avec un lien de connexion valable **7 jours** (`MagicLink::send($user, invitation: true)`), au lieu de 30 min pour un lien demandé depuis la page de connexion.
- Premier admin créé par migration à partir de `ADMIN_EMAIL`.

### Connecteur Claude (MCP) — décision du 30/10/2026
Objectif : depuis claude.ai, créer un article à partir d'un document (images comprises) et modifier une page du site, sans compétence technique.
- Serveur MCP `App\Mcp\Servers\SiteServer` sur **`/mcp`** (`routes/ai.php`), protégé par **OAuth 2.1 (Passport)** : claude.ai s'inscrit seul (`/oauth/register`, inscription dynamique), l'utilisateur se connecte (lien magique) puis valide la page « Autoriser Claude ? » (`resources/views/mcp/authorize.blade.php`). Seules les adresses de retour `claude.ai` / `claude.com` sont acceptées (`config/mcp.php`).
- Outils : `list-articles`, `get-page` (article par slug/URL/lien admin, accueil, pages légales), `create-article` (**brouillon** par défaut, renvoie `admin_url` + `preview_url`), `update-article`, `update-home-section`, `update-legal-page`, `search-library` (avec vignettes), `view-library-image`, `prepare-article-image` (copie pour l'article), `update-library-image`, `import-image-from-url` (vers la bibliothèque, protégé contre les adresses internes). **Aucun outil de suppression.** Les consignes de rédaction sont dans les `#[Instructions]` du serveur.
- Images : Claude ne peut pas transmettre les images d'un document → il pioche dans la **bibliothèque d'images** ; s'il en manque, il demande à l'utilisateur de les y ajouter d'abord.
- Mode d'emploi pour les utilisateurs + liste/coupure de leurs connexions : Admin > **Connecter Claude** (`App\Filament\Pages\ConnectClaude`).
- Jetons OAuth : accès 7 jours, renouvellement 1 an. Un utilisateur désactivé perd l'accès (middleware `active`). Clés `storage/oauth-*.key` générées par `deploy.sh` (hors git).

### Bibliothèque d'images — décision du 30/10/2026
- Admin > **Bibliothèque d'images** (`LibraryImageResource`, table `library_images`, `App\Support\Library`) : les **sources**. Ajout de photos ou d'un **.docx** (images extraites de `word/media`, dans l'ordre du document).
- **Dédoublonnage** : même fichier (sha256) ou même image dans un autre format/taille/compression (empreintes perceptuelles dHash + pHash de `jenssegers/imagehash`, seuils 5 et 8 sur 64 bits, mêmes proportions à 2 %). Mesuré : variantes d'une même photo ≤ 3, photos différentes ≥ 17 ; un recadrage compte comme une autre image. Si la nouvelle est **de meilleure qualité** (> 5 % de pixels en plus, ou autant de pixels et fichier 20 % plus lourd), elle **remplace** la source (titre et description conservés).
- Originaux **privés** (disque `local`, `bibliotheque/AAAA/MM/…`, téléchargeables via lien signé : ils peuvent contenir des EXIF/GPS) ; vignettes WebP publiques (`bibliotheque/` sur `public`).
- **Les pages n'utilisent jamais une source directement** : copie retraitée (WebP, 1920 px, sans métadonnées), enregistrée dans `library_copies` avec la version de la source (`source_sha256`). Index des pages qui utilisent chaque copie : `library_usages` (`App\Support\LibraryUsages`), recalculé à chaque enregistrement d'un article ou de l'accueil (hooks `saved` des modèles `Article` et `Setting`).
- **Versions** : « Nouvelle version » (fiche de l'image) et le remplacement automatique du dédoublonnage remplacent la source **sans toucher aux pages** : leurs copies passent en « Ancienne version ». On remplace ensuite l'image **page par page** (case à cocher par page dans la fiche, action « Remplacer » sur une page ou sur la sélection, ou « Mettre à jour toutes les pages ») : `Library::replaceInPage` crée une **nouvelle copie à une nouvelle adresse** (jamais de réécriture au même chemin : les navigateurs garderaient l'ancienne image en cache), met à jour les références de cette page seulement (couverture, galerie, texte, valeurs de l'accueil) et efface l'ancienne copie quand plus rien ne l'utilise. Taille : par défaut celle de la copie actuelle dans la page (« dans son contexte »), ou 480 / 800 / 1 200 / 1 920 px max.
- **Suppression** (`Library::delete`) : refusée si une page en ligne l'utilise (article publié, même programmé, ou accueil) ; sinon l'image est retirée des brouillons (couverture, galerie, `![…](…)` et `<img>` du corps).
- Grille (`ListLibraryImages`, page Livewire) : défilement infini (24 par 24), tri par ajout / dernière utilisation / nombre d'utilisations, recherche mot à mot (titre, description, nom de fichier, **titre des articles** qui l'utilisent — scope `LibraryImage::search`, aussi utilisé par Claude), images affichées en entier, **glisser-déposer** de fichiers sur la grille, **sélection multiple** (case sur chaque carte ; en mode sélection, un clic sélectionne au lieu d'ouvrir la fiche) pour **télécharger en ZIP** (`/bibliotheque/archive?ids=…`, `LibraryArchiveController`, hors `/admin` car Filament capte `/admin/bibliotheque/{id}`) ou **supprimer** (mêmes règles qu'une suppression simple : images utilisées par une page en ligne conservées). Clic → fiche (`EditLibraryImage`) : titre/description, format, compression (qualité JPEG estimée par `App\Support\ImageInfo`), taille, poids, original, pages qui l'utilisent (`UsagesRelationManager`).
- **Formulaire d'article** : actions « Choisir dans la bibliothèque » (image de partage), « Ajouter depuis la bibliothèque » (galerie, sélection multiple ordonnée) et « Insérer une image de la bibliothèque » (corps Markdown, **à l'endroit du curseur** via `window.sjInsertMarkdown`, `resources/views/filament/scripts.blade.php`, qui pilote le CodeMirror d'EasyMDE). Champ `App\Filament\Forms\LibraryPicker` (grille, recherche JSON `/bibliotheque/recherche`), actions `App\Filament\Forms\LibraryActions`. Chaque image choisie devient une copie propre à l'article ; dans le corps, adresse relative `/storage/…`.
- **Taille des images du texte** : `![description](/storage/…){width=400}` (extension Attributes de CommonMark, seul l'attribut `width` est autorisé). Choisie à l'insertion (pleine largeur, 600, 400, 250 ou autre) ou modifiée à la main dans le texte : à l'enregistrement (`Article` → `saving` → `Library::resizeBodyImages`), si le fichier n'a pas la bonne taille, nouvelle copie depuis la bibliothèque à **2 × la largeur affichée** (écrans haute densité), sans dépasser l'original ni 1920 px, et adresse mise à jour. Images hors bibliothèque laissées telles quelles. Claude : `prepare-article-image` avec `display_width`.
- Copies inutilisées (image choisie puis article non enregistré, article supprimé…) effacées après un jour : `php artisan bibliotheque:nettoyer`, lancé par `deploy.sh`.
- **Recadrer / pivoter** (fiche d'une image) : Cropper.js 1.6.2 (npm, entrée Vite `resources/js/admin/cropper.js` chargée dans l'admin) choisit la zone, les quarts de tour, le miroir et les proportions (libre, carré, 4:3, 3:2, 16:9, partage 1200 × 630) ; le serveur applique la retouche **sur l'original en pleine résolution** (`Library::edit` : miroir → rotation → recadrage, même format, qualité 92). C'est une nouvelle version : les pages passent en « Ancienne version ». L'original précédent est gardé (`previous_source_path`) pour « Annuler la dernière retouche » (`Library::undoEdit`, un niveau).
- Envois Livewire limités à 30 Mo (`config/livewire.php`, au lieu de 12 Mo par défaut).
- Toute image envoyée dans l'admin (couverture, galerie, images du corps Markdown, page d'accueil) ou par l'API rejoint aussi la bibliothèque (`Library::addQuietly`, n'interrompt jamais l'envoi). Les images des articles existants y ont été versées par migration.

### API REST (Claude Code)
Jeton personnel (Passport) créé dans Admin > Utilisateurs > ⋯ > « Créer un jeton API ». En-têtes : `Authorization: Bearer <jeton>`, `Accept: application/json`.
- `GET /api/articles[?status=draft&category=equipe]` · `GET /api/articles/{slug}` (avec `body`)
- `POST /api/articles` (title, category requis ; format, body, excerpt, slug, status, published_at, is_pinned, og_image, og_image_alt, gallery[{path,alt}])
- `PATCH /api/articles/{slug}` · `DELETE /api/articles/{slug}`
- `POST /api/media` (multipart `file`, `directory?`, `max_width?`) → `{path, url}` ; `path` s'utilise dans `og_image`/`gallery`, `url` dans le HTML.
Les articles créés via l'API sont en **brouillon** par défaut ; renvoyer `preview_url` pour relecture.

### Formulaires
- Newsletter : **Brevo** API, liste **2**, attributs `PRENOM` / `NOM`, **sans double opt-in** (choix du projet : public âgé). `App\Services\Brevo`.
- Contact : email envoyé à `CONTACT_EMAIL` (information.saint-joseph@saint-arnoux.fr), reply-to = visiteur, **non stocké**.
- Anti-spam (`App\Support\SpamGuard`) : honeypot `website` + horodatage chiffré `_t` (≥ 3 s) + **Cloudflare Turnstile** sur le contact si `TURNSTILE_*` renseignés. Rate limiting sur les routes.
- Emails transactionnels via **SMTP Brevo** (à configurer dans le `.env` de prod).

### Statistiques
Matomo auto-hébergé https://matomo.saint-arnoux.fr/, site **ID 3**, `disableCookies` → pas de bandeau cookies. Chargé uniquement si `APP_ENV=production`.

## Déploiement (O2Switch)
- **Décision du 29/10/2026** : le serveur récupère le code avec git (pas de FTP, GitHub n'a aucun accès au serveur ; le SSH O2Switch est limité aux IP autorisées, incompatible avec les runners GitHub). Le FTP fichier par fichier était beaucoup trop lent (> 3 h pour ~18 000 fichiers).
- GitHub Action `.github/workflows/deploy.yml` sur push `main` : tests → `npm run build` → branche **`production`** = main + `public/build` (ignoré sur main), un commit poussé en force. Aucun secret GitHub nécessaire.
- Sur le serveur (npm absent, Composer présent), dans le dossier du site cloné depuis GitHub (dépôt public) : **`./deploy.sh`** → `git fetch` + `git checkout -f -B production origin/production`, `composer install --no-dev`, `migrate --force`, `storage:link`, `optimize`, `filament:optimize`, avec page de maintenance pendant l'opération. Ne fait rien si déjà à jour (`--force` pour tout relancer) → utilisable en cron cPanel. Variables `PHP=` / `COMPOSER=` si les binaires par défaut ne sont pas les bons.
- **Toute évolution de schéma ou de contenu initial passe par une migration** (elles tournent à chaque déploiement). Le contenu de départ est posé par la migration `seed_initial_content` → `Database\Seeders\InitialContentSeeder` (idempotent, ignoré en tests).
- Racine du sous-domaine à faire pointer sur `public/` dans cPanel (sinon `.htaccess` racine redirige vers `public/`).
- Pas de worker : `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, sessions en base.
- Secrets : jamais dans git. Clé Brevo, SMTP uniquement dans le `.env` du serveur. **La clé Brevo a circulé en clair : la régénérer avant la mise en production.**

## Conventions de code
- Code et commentaires en français pour ce qui est métier ; noms de classes/méthodes en anglais (Laravel).
- Attributs PHP Laravel 13 (`#[Fillable]`, `#[Hidden]`) sur les modèles.
- Styles : variables de charte dans `resources/scss/_variables.scss` (navy `#00204E`, or `#C5A24B`, parchemin `#F6F1E6`), un partial par zone. Les styles du corps d'article sont dans `_prose.scss`, réutilisés par l'aperçu admin (`preview.scss`, isolé sous `.sj-preview`, sans Bootstrap).
- Réglages éditables : `Setting::get('home.collecte.raised')` / `Setting::put('home', [...])`. Pour un nouveau bloc éditable, ajouter le champ dans `ManageHome`/`ManageSite` **et** une valeur par défaut via migration.
- Blade : **ne pas utiliser `@js(...)` dans un attribut de composant** (`<x-filament::button x-on:click="…@js(…)…">`) : la directive n'y est pas interprétée et le JavaScript est cassé. Utiliser `{{ … }}`, ou mettre la logique dans un `x-data` sur une balise HTML ordinaire.
- Tests : `tests/Feature` (SQLite mémoire). Les lancer avant chaque commit.
