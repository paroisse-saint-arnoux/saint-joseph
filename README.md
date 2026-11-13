# Site Saint Joseph (Le Rouret)

Site du projet de centre paroissial et d'église Saint Joseph, porté par la Paroisse Saint-Arnoux
(Association Diocésaine de Nice). Production : https://saint-joseph.saint-arnoux.fr/

Laravel 13 · Filament 5 (administration) · SCSS + Bootstrap · MySQL · hébergé sur O2Switch.
Les choix techniques et fonctionnels sont détaillés dans [CLAUDE.md](CLAUDE.md).
Le connecteur Claude (serveur MCP) est documenté dans [docs/mcp.md](docs/mcp.md).

## Mettre à jour le contenu (sans programmation)

Rendez-vous sur **/admin**, saisissez votre adresse email : vous recevez un lien de connexion.

- **Articles** : créer, modifier, publier ou laisser en brouillon. Un brouillon peut être partagé grâce à son lien de prévisualisation.
- **Page d'accueil** : textes, chiffres clés, visuels, frise d'avancement, **montant collecté**.
- **Réglages du site** : réseaux sociaux, liens utiles, textes newsletter/don, mentions légales.
- **Utilisateurs** (administrateurs) : ajouter les personnes autorisées à se connecter.

## Développer en local

Prérequis : Docker, Node 22.

```bash
cp .env.example .env              # puis adapter (voir ci-dessous)
docker compose up -d
docker compose exec -u www-data -e HOME=/tmp app composer install
docker compose exec -u www-data app php artisan key:generate
docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app php artisan storage:link
npm install && npm run dev
```

Dans `.env` pour Docker : `APP_ENV=local`, `APP_DEBUG=true` (`APP_URL` est fixé par `docker-compose.yml`),
`DB_HOST=db`, `DB_DATABASE=sjo2`, `DB_USERNAME=sjo2`, `DB_PASSWORD=sjo2`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`,
et `ADMIN_EMAIL=votre@email` (premier administrateur).

Tout passe par Traefik sur `*.dev.saint-arnoux.fr` (ce domaine renvoie vers `127.0.0.1`) :

- Site : https://saint-joseph.dev.saint-arnoux.fr — Admin : https://saint-joseph.dev.saint-arnoux.fr/admin
- Emails reçus : https://saint-joseph-mail.dev.saint-arnoux.fr (Mailpit)
- Base de données : https://saint-joseph-db.dev.saint-arnoux.fr (DbGate, connexion préconfigurée)
- Certificat https : généré en local par mkcert, voir [docker/traefik/certs/README.md](docker/traefik/certs/README.md). Sans lui, certificat auto-signé (ou utiliser http://).
- Tests : `docker compose exec -u www-data app php artisan test`

## Mise en production (première fois)

1. **cPanel O2Switch** : créer la base MySQL et son utilisateur ; faire pointer la racine du
   sous-domaine `saint-joseph.saint-arnoux.fr` vers le dossier `…/public` du site.
2. **Brevo** : régénérer la clé API, créer une clé SMTP, authentifier le domaine `saint-arnoux.fr` (SPF/DKIM).
3. **Cloudflare Turnstile** (facultatif) : créer un widget et récupérer les deux clés.
4. **Serveur (SSH)** : cloner le dépôt dans le dossier du site, puis créer le `.env` à partir de
   `.env.example` (`APP_KEY` générée avec `php artisan key:generate --show`, base de données,
   SMTP Brevo, `BREVO_API_KEY`, `ADMIN_EMAIL`).
5. Sur le serveur : `./deploy.sh` (git pull de `main`, Composer, migrations, caches).
   Si la commande `php` n'est pas en version 8.5 : `PHP=/opt/alt/php85/usr/bin/php ./deploy.sh`.

## Mettre à jour le site

1. **En local** : après une modification SCSS ou JS, lancer `npm run build` et commiter `public/build` avec le reste
   (le serveur n'a pas npm). L'action GitHub **Tests** échoue si `public/build` n'est pas à jour, ou si un test échoue.
2. Fusionner dans **`main`** et pousser, en attendant que l'action soit au vert.
3. **Sur le serveur**, en SSH : `cd ~/DOSSIER_DU_SITE && ./deploy.sh`.
   Page de maintenance de quelques secondes, puis `git pull --ff-only origin main`, `composer install --no-dev`,
   `migrate --force`, lien `storage`, clés OAuth (une fois), nettoyage de la bibliothèque, caches.
