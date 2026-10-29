# CLAUDE.md — Site Saint Joseph (projet SJO2)

Site du projet de centre paroissial et d'église Saint Joseph (Paroisse Saint-Arnoux, Le Rouret).
Production : https://saint-joseph.saint-arnoux.fr/ — hébergement mutualisé **O2Switch** (LAMP, PHP 8.5, MySQL, **pas d'accès SSH**, déploiement par FTP).

Ce fichier consigne les décisions prises avec Bertrand Gorge. **Le tenir à jour** à chaque nouvelle décision.

## Stack

- **Laravel 13** + **Filament 5** (admin en français, `/admin`), Livewire 4, Sanctum (API).
- Front : Blade + **SCSS + Bootstrap 5.3** (import sélectif, pas de Tailwind), Vite, JS vanilla en modules.
- Polices Fraunces / Hanken Grotesk auto-hébergées (`@fontsource-variable`, pas de Google Fonts).
- Images : `intervention/image` (GD) — tout upload est redimensionné (1920 px max) et converti en **WebP** (`App\Support\Images`).
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
- Champs : titre, slug, intro (excerpt), image de partage (OG) + alt, corps, galerie (carrousel, alt obligatoire), statut, date de publication modifiable (date future = programmé), épinglé.
- **Deux formats** : `markdown` (édité dans l'admin, onglets Rédaction/Aperçu) et `html` (mis en page, généré par IA, ex. article Équipe ; éditeur de code HTML dans l'admin, écrit surtout via l'API).
- **Brouillons** : absents des listes et du sitemap, `noindex`, accessibles seulement via `?apercu={preview_token}` (ou si connecté à l'admin).
- Pas d'auteur affiché, pas de temps de lecture. Articles liés = même catégorie.
- Classes CSS disponibles pour les articles HTML : `.moa`, `.comp-grid/.comp`, `.benev`, `.team-grid/.member` (avec `.member-role`/`.member-bio` cachés → modale au clic), `.team-verse`, plus tout Bootstrap (grid, `.ratio`…).

### Admin & accès
- Connexion **par lien magique uniquement** (pas de mot de passe) : `App\Filament\Pages\Auth\Login` + `App\Support\MagicLink`. Lien à usage unique, 30 min, signé ; le lien mène à une page qui s'auto-soumet en JS (POST) puis redirige vers l'admin : l'utilisateur arrive directement connecté, et les antivirus des messageries (qui ouvrent les liens sans exécuter le JS) ne consomment pas le lien. Bouton de secours dans `<noscript>`.
- Liste blanche = table `users` (champ `is_active`). Rôles : **admin** (gère les utilisateurs) / **editor** (contenu). Les éditeurs publient directement.
- Premier admin créé par migration à partir de `ADMIN_EMAIL`.

### API (pour Claude Code)
Jeton Sanctum créé dans Admin > Utilisateurs > ⋯ > « Créer un jeton API ». En-têtes : `Authorization: Bearer <jeton>`, `Accept: application/json`.
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

## Déploiement (O2Switch, sans SSH)
- GitHub Action `.github/workflows/deploy.yml` sur push `main` : tests → `composer install --no-dev` → `npm run build` → **FTP** (FTP-Deploy-Action, envoi différentiel) → `POST /api/deploy` (Bearer `DEPLOY_TOKEN`) qui lance `migrate --force`, `storage:link`, `optimize`, `filament:optimize`.
- **Toute évolution de schéma ou de contenu initial passe par une migration** (elles tournent automatiquement au déploiement). Le contenu de départ est posé par la migration `seed_initial_content` → `Database\Seeders\InitialContentSeeder` (idempotent, ignoré en tests).
- Racine du sous-domaine à faire pointer sur `public/` dans cPanel (sinon `.htaccess` racine redirige vers `public/`).
- Pas de worker : `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, sessions en base.
- Secrets : jamais dans git. Clé Brevo, SMTP, `DEPLOY_TOKEN` uniquement dans le `.env` du serveur et les secrets GitHub. **La clé Brevo a circulé en clair : la régénérer avant la mise en production.**

## Conventions de code
- Code et commentaires en français pour ce qui est métier ; noms de classes/méthodes en anglais (Laravel).
- Attributs PHP Laravel 13 (`#[Fillable]`, `#[Hidden]`) sur les modèles.
- Styles : variables de charte dans `resources/scss/_variables.scss` (navy `#00204E`, or `#C5A24B`, parchemin `#F6F1E6`), un partial par zone. Les styles du corps d'article sont dans `_prose.scss`, réutilisés par l'aperçu admin (`preview.scss`, isolé sous `.sj-preview`, sans Bootstrap).
- Réglages éditables : `Setting::get('home.collecte.raised')` / `Setting::put('home', [...])`. Pour un nouveau bloc éditable, ajouter le champ dans `ManageHome`/`ManageSite` **et** une valeur par défaut via migration.
- Tests : `tests/Feature` (SQLite mémoire). Les lancer avant chaque commit.
