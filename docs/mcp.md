# Connecteur Claude (serveur MCP) du site Saint Joseph

Le site expose un serveur **MCP** (Model Context Protocol) qui permet de travailler sur le site depuis **claude.ai** (web, application de bureau, mobile), en langage courant et sans compétence technique :

- rédiger un article à partir d'un document, images comprises ;
- modifier un article, la page d'accueil ou les pages légales.

- **Adresse** : `https://saint-joseph.saint-arnoux.fr/mcp`
- **Nom affiché dans Claude** : « Site Saint Joseph » (version 1.0.0)
- **Code** : `app/Mcp/Servers/SiteServer.php` (serveur et consignes), `app/Mcp/Tools/` (un fichier par outil), `routes/ai.php` (routes)
- **Tests** : `tests/Feature/McpTest.php`

Pour l'API REST (Claude Code, scripts), voir la section « API REST » de [CLAUDE.md](../CLAUDE.md).

## Connecter Claude au site (utilisateurs)

Le mode d'emploi pas à pas, avec captures, se trouve dans **Admin > Administration > Connecter Claude** (`App\Filament\Pages\ConnectClaude`). En résumé :

1. Dans claude.ai, ouvrir **Personnaliser > Connecteurs**.
2. Choisir **+ Ajouter > Ajouter un connecteur personnalisé**.
3. Saisir le nom « Site Saint Joseph » et l'adresse `https://saint-joseph.saint-arnoux.fr/mcp`.
4. Se connecter : Claude ouvre la page de connexion du site, qui envoie un lien magique par email.
5. Sur la page **« Autoriser Claude ? »**, valider.
6. Dans une conversation, vérifier dans le menu des outils que « Site Saint Joseph » est activé.

La même page de l'admin liste les connexions de l'utilisateur et permet de les couper.

## Sécurité et authentification

- **OAuth 2.1 avec Passport.** Claude s'inscrit seul comme client (inscription dynamique sur `/oauth/register`), puis l'utilisateur se connecte et donne son accord.
  - Routes de découverte (`.well-known`) et d'inscription : `Mcp::oauthRoutes()`, limitées à 10 requêtes par minute (`throttle:10,1,oauth`).
  - Page d'autorisation : `resources/views/mcp/authorize.blade.php` (`Passport::authorizationView('mcp.authorize')`).
- **Adresses de retour autorisées** : `https://claude.ai` et `https://claude.com` uniquement (`config/mcp.php`, `redirect_domains`). Aucun autre logiciel ne peut se connecter.
- **Middlewares sur `/mcp`** :
  - `auth:api` : jeton OAuth obligatoire ;
  - `active` (`App\Http\Middleware\EnsureUserIsActive`) : un utilisateur désactivé dans l'admin perd l'accès immédiatement. Le middleware enregistre aussi la dernière utilisation (`last_mcp_at`, colonne « Claude » de la liste des utilisateurs) ;
  - `throttle:120,1,mcp` : 120 appels par minute.
- **Jetons** (`AppServiceProvider`) :
  - jeton d'accès valable 7 jours, renouvelé automatiquement par Claude pendant 1 an ;
  - au-delà, il faut se reconnecter.
- **Clés** : `storage/oauth-*.key`, générées une seule fois par `deploy.sh` (`passport:keys`) et jamais dans git.
- Les outils agissent **au nom de l'utilisateur connecté** : l'auteur d'un article créé par Claude est cet utilisateur.
- **Aucun outil de suppression**, ni pour les articles ni pour les images.
- `import-image-from-url` refuse les adresses internes (protection SSRF, `App\Support\RemoteImage`) :
  - seules les adresses http(s) publiques sont acceptées, redirections comprises ;
  - les IP privées et réservées sont refusées ;
  - l'image fait 15 Mo au maximum.

## Consignes données à Claude

L'attribut `#[Instructions]` de `SiteServer` est transmis à Claude à chaque connexion. Il fixe :

- **Le public.** Les utilisateurs sont des bénévoles souvent peu familiers de l'informatique : Claude explique simplement, en français.
- **La création d'un article**, en cinq étapes :
  1. Choisir une seule catégorie.
  2. Rédiger fidèlement au document : rien d'inventé (chiffres, dates, noms), titre et chapô non répétés dans le corps.
  3. Prendre les images uniquement dans la bibliothèque.
  4. Créer l'article en brouillon.
  5. Donner à l'utilisateur `admin_url` et `preview_url`.
- **Les images.** Si des images manquent, Claude demande à l'utilisateur de les ajouter à la bibliothèque, par exemple en y glissant directement son fichier Word, et ne les invente jamais. Chaque image a une description (alt).
- **La modification d'une page.** Claude lit toujours la page avec `get-page` avant de la modifier, garde tout le reste à l'identique, puis résume ce qui a changé.
- **La mise en forme.**
  - Le Markdown GitHub est le format par défaut. Une image se dimensionne avec `{width=400}`, et un lien YouTube seul sur sa ligne devient une vidéo.
  - Le HTML n'est utilisé que si une mise en page riche est demandée.
- **Le ton.**
  - Ton chaleureux et sobre ; écrire « Saint Joseph » et **jamais « SJO2 »**.
  - Pas d'auteur, pas de temps de lecture.

Quand une règle de rédaction change, il faut mettre à jour ces consignes (et les descriptions des outils concernés).

## Outils

Les outils renvoient en général un court message suivi d'un objet JSON (trait `Concerns\JsonResponse`). Les erreurs sont des messages en français, rédigés pour que Claude puisse corriger sa demande seul : champ inconnu, image introuvable, alt manquant…

| Outil | Rôle | Annotations |
|---|---|---|
| `list-articles` | Lister les articles | lecture seule |
| `get-page` | Lire une page modifiable | lecture seule |
| `create-article` | Créer un article (brouillon) | |
| `update-article` | Modifier un article | idempotent |
| `update-home-section` | Modifier une section de l'accueil | idempotent |
| `update-legal-page` | Remplacer les mentions légales ou la confidentialité | idempotent |
| `search-library` | Chercher dans la bibliothèque d'images | lecture seule |
| `view-library-image` | Voir une image en grand | lecture seule |
| `prepare-article-image` | Préparer la copie d'une image pour un article | |
| `update-library-image` | Renommer ou décrire une image | idempotent |
| `import-image-from-url` | Importer une image du web dans la bibliothèque | accès au web |

### Articles

#### `list-articles`
Liste les articles publiés et les brouillons : les épinglés d'abord, puis du plus récent au plus ancien, 100 au maximum. Le corps du texte n'est pas inclus.

| Paramètre | Type | Description |
|---|---|---|
| `status` | `draft` \| `published` | Filtre par statut |
| `category` | catégorie | Filtre par catégorie |
| `search` | texte | Mot à chercher dans le titre |

**Réponse** : liste d'articles, chacun décrit par les champs de la réponse « Article » ci-dessous, sans `body`.

#### `get-page`
Lit le contenu modifiable d'une page. **À appeler avant toute modification.**

| Paramètre | Type | Description |
|---|---|---|
| `page` (requis) | texte | Adresse publique, lien d'aperçu, lien d'édition de l'admin, slug d'un article, ou `/` (ou `accueil`) pour la page d'accueil |

**Réponse** selon la page :
- **Un article** : tous ses champs, corps compris (voir la réponse « Article » ci-dessous).
- **L'accueil (`/`)** : les sections et leurs champs, tels que les attend `update-home-section`.
- **`/mentions-legales` ou `/confidentialite`** : `{page: "mentions" | "privacy", markdown}`.

#### `create-article`
Crée un article, **en brouillon par défaut**. Les champs (trait `Concerns\ArticleSchema`, règles de validation `App\Support\ArticleData::rules()`) :

| Champ | Type | Description |
|---|---|---|
| `title` (requis) | texte | Titre |
| `category` (requis) | `agenda`, `architecture`, `budget`, `construction`, `projet`, `presentation`, `equipe`, `presse` | Une seule catégorie |
| `excerpt` | texte | Chapô de 1 à 2 phrases (400 caractères maximum), affiché dans les listes et les partages |
| `format` | `markdown` (par défaut) \| `html` | Format du corps |
| `body` | texte | Corps, sans répéter le titre ni le chapô |
| `og_image` | chemin | Image de couverture et de partage : `path` renvoyé par `prepare-article-image` |
| `og_image_alt` | texte | Description de la couverture, **obligatoire si `og_image` est rempli** |
| `gallery` | liste de `{path, alt}` | Carrousel en fin d'article ; remplace la galerie existante |
| `status` | `draft` \| `published` | `published` seulement si l'utilisateur le demande |
| `published_at` | date ISO 8601 | Une date future programme la publication |
| `is_pinned` | booléen | Épingler l'article en tête de liste |
| `show_cover` | booléen | Afficher la couverture en haut de l'article (oui par défaut) |
| `slug` | texte | Fin de l'adresse ; généré depuis le titre s'il est absent |

**Réponse « Article »** : `id`, `title`, `slug`, `category`, `excerpt`, `format`, `body` (seulement avec `get-page`), `og_image`, `og_image_url`, `og_image_alt`, `gallery`, `status`, `published_at`, `is_pinned`, `show_cover`, `url`, `preview_url`, `admin_url`, `updated_at`.

#### `update-article`
Modifie un article existant : **seuls les champs fournis changent**. `body` remplace tout le corps, il faut donc partir du texte renvoyé par `get-page`. Le statut ne change pas sauf demande explicite.

| Paramètre | Type | Description |
|---|---|---|
| `article` (requis) | texte | Slug, adresse publique ou lien d'édition de l'article |
| … | | Mêmes champs que `create-article`, tous facultatifs |

Si la catégorie change, l'ancienne adresse redirige vers la nouvelle (301). La modification d'un article publié est en ligne immédiatement.

**Réponse** : l'article à jour, avec ses liens d'édition et d'aperçu.

### Page d'accueil et pages légales

#### `update-home-section`
Modifie une section de la page d'accueil, qui est **en ligne immédiatement**. Les données sont dans le réglage `home` de la table `settings`.

| Paramètre | Type | Description |
|---|---|---|
| `section` (requis) | `hero`, `decouvrir`, `projet`, `avancement`, `collecte` | Section à modifier |
| `content` (requis) | objet | Seulement les champs à changer, avec les mêmes noms et la même forme que dans `get-page` |

Champs de chaque section :

| Section | Champs |
|---|---|
| `hero` | `title`, `image`, `facts` |
| `decouvrir` | `eyebrow`, `title`, `text`, `image`, `image_alt` |
| `projet` | `eyebrow`, `title`, `intro`, `stats`, `buildings`, `images` |
| `avancement` | `eyebrow`, `title`, `text`, `steps` (chaque étape a un `status` : `done` / `now` / `todo`) |
| `collecte` | `eyebrow`, `title`, `text`, `quote`, `raised`, `goal`, `goal_label` |

Règles :
- Un champ inconnu est refusé, et le message d'erreur donne la liste des champs possibles.
- Une liste fournie (`facts`, `stats`, `buildings`, `images`, `steps`) remplace toute la liste.
- `raised` et `goal` sont des nombres en euros.
- Dans les titres et les textes courts, `*texte*` met en valeur (italique doré) et `**texte**` met en gras.

#### `update-legal-page`
Remplace tout le texte d'une page légale, qui est en ligne immédiatement. Il faut d'abord lire le texte actuel avec `get-page`.

| Paramètre | Type | Description |
|---|---|---|
| `page` (requis) | `mentions` \| `privacy` | Mentions légales ou politique de confidentialité |
| `markdown` (requis) | texte | Texte complet de la page |

### Bibliothèque d'images

Claude ne peut pas transmettre au site les images d'un document. **Toutes les images passent donc par la bibliothèque** (Admin > Contenu > Bibliothèque d'images) : l'utilisateur y dépose ses photos ou son fichier Word, puis Claude les retrouve. Les pages n'utilisent jamais une source directement : elles utilisent une copie propre à l'article, créée par `prepare-article-image`. Le fonctionnement de la bibliothèque est décrit dans [CLAUDE.md](../CLAUDE.md).

#### `search-library`
Cherche des images, les plus récentes d'abord, avec une **vignette** de chaque image. Chaque mot est cherché dans le titre, la description, le nom du fichier d'origine et le titre des articles qui utilisent l'image. Sans `query`, l'outil renvoie les dernières images ajoutées (utile juste après un ajout par l'utilisateur).

| Paramètre | Type | Description |
|---|---|---|
| `query` | texte | Mot(s) à chercher |
| `page` | entier | Page de résultats (1 par défaut) |
| `per_page` | entier | Nombre d'images par page (12 par défaut, 20 au maximum) |

**Réponse** :
- le nombre total d'images trouvées et la page affichée ;
- pour chaque image : `id`, `title`, `alt`, `original_name`, `dimensions`, `used_in_pages`, `added_at` ;
- les vignettes WebP, dans le même ordre.

Si rien n'est trouvé, l'outil renvoie l'adresse de la bibliothèque, pour que Claude invite l'utilisateur à y ajouter des images.

#### `view-library-image`
Affiche une image en plus grand (1024 px), pour la décrire ou la choisir.

| Paramètre | Type | Description |
|---|---|---|
| `id` (requis) | entier | Identifiant de l'image |

#### `prepare-article-image`
Crée une copie de l'image, redimensionnée, en WebP et sans métadonnées, propre à l'article. Il faut l'appeler **une fois par image utilisée**.

| Paramètre | Type | Description |
|---|---|---|
| `id` (requis) | entier | Identifiant de l'image dans la bibliothèque |
| `display_width` | entier, de 50 à 1200 | Largeur d'affichage dans le texte. Le fichier fait alors 2 × cette largeur (écrans haute densité), sans dépasser l'original ni 1920 px. Sans ce paramètre, l'image prend toute la largeur du texte. |
| `alt` | texte | Description à utiliser à la place de celle de la bibliothèque |

**Réponse** :
- `path` : à utiliser pour `og_image` ou `gallery` ;
- `alt` ;
- `markdown` : à placer tel quel dans le corps, sur sa propre ligne, par exemple `![…](/storage/articles/…){width=400}`.

#### `update-library-image`
Renomme une image ou complète sa description, pour la retrouver et la réutiliser plus facilement.

| Paramètre | Type | Description |
|---|---|---|
| `id` (requis) | entier | Identifiant de l'image |
| `title` | texte | Titre court |
| `alt` | texte | Description en une phrase, pour les personnes malvoyantes |

#### `import-image-from-url`
Ajoute à la bibliothèque une image publiée sur le web. Il faut l'adresse directe de l'image.

| Paramètre | Type | Description |
|---|---|---|
| `url` (requis) | adresse http(s) | Adresse de l'image |
| `title` | texte | Titre court |
| `alt` | texte | Description |

Le dédoublonnage de la bibliothèque s'applique : si l'image y est déjà, l'outil renvoie l'image existante, et la remplace si la nouvelle est de meilleure qualité. On utilise ensuite `prepare-article-image` avec l'`id` renvoyé.

## Exemple de déroulé

> « Voici le compte rendu de la bénédiction du terrain, fais-en un article avec les photos que je viens d'ajouter. »

1. `search-library` sans `query` : Claude voit les dernières photos ajoutées, avec leurs vignettes.
2. `view-library-image` sur une ou deux photos, pour bien les décrire.
3. `update-library-image` pour les photos qui n'ont pas de description.
4. `prepare-article-image` pour chaque photo retenue : une pour la couverture, d'autres pour la galerie ou pour le texte (`display_width` pour une image affichée en petit).
5. `create-article` avec la catégorie `agenda`, le titre, le chapô, le corps en Markdown, `og_image` + `og_image_alt` et `gallery`. L'article est en brouillon.
6. Claude donne à l'utilisateur le **lien d'aperçu** et le **lien d'édition** dans l'admin, où l'utilisateur relit et publie.

## Ajouter ou modifier un outil

1. Créer la classe dans `app/Mcp/Tools/`, en étendant `Laravel\Mcp\Server\Tool`. Lui donner :
   - une `#[Description]` en français, rédigée pour Claude : quand utiliser l'outil, quoi faire ensuite ;
   - les annotations utiles : `#[IsReadOnly]`, `#[IsIdempotent]`, `#[IsOpenWorld]`.
2. Écrire `schema()` (paramètres et leurs descriptions) et `handle()`.
   - Valider la requête avec `$request->validate()`.
   - Renvoyer `Response::error()` avec un message qui explique comment corriger.
3. Ajouter la classe à `$tools` dans `SiteServer`. Si besoin, compléter les consignes `#[Instructions]` et cette documentation.
4. Ne pas ajouter d'outil de suppression sans décision explicite.
5. Ajouter un test dans `tests/Feature/McpTest.php`.

Pour essayer en local, on peut utiliser l'inspecteur MCP (`php artisan mcp:inspector mcp`, dans le conteneur), ou connecter claude.ai à une adresse publique de test.
