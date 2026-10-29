# Site Saint Joseph — Projet SJO2 (Le Rouret)

Site du projet de centre paroissial et d'église Saint Joseph, porté par la Paroisse Saint-Arnoux
(Association Diocésaine de Nice). Production : https://saint-joseph.saint-arnoux.fr/

Laravel 13 · Filament 5 (administration) · SCSS + Bootstrap · MariaDB · hébergé sur O2Switch.
Les choix techniques et fonctionnels sont détaillés dans [CLAUDE.md](CLAUDE.md).

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

Dans `.env` pour Docker : `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8080`,
`DB_HOST=db`, `DB_DATABASE=sjo2`, `DB_USERNAME=sjo2`, `DB_PASSWORD=sjo2`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`,
et `ADMIN_EMAIL=votre@email` (premier administrateur).

- Site : http://localhost:8080 — Admin : http://localhost:8080/admin
- Emails reçus : http://localhost:8025 (Mailpit)
- Base de données : http://localhost:3000 (DbGate, connexion préconfigurée)
- Tests : `docker compose exec -u www-data app php artisan test`

## Mise en production (première fois)

1. **cPanel O2Switch** : créer la base MariaDB et son utilisateur ; faire pointer la racine du
   sous-domaine `saint-joseph.saint-arnoux.fr` vers le dossier `…/public` du site.
2. **Brevo** : régénérer la clé API, créer une clé SMTP, authentifier le domaine `saint-arnoux.fr` (SPF/DKIM).
3. **Cloudflare Turnstile** (facultatif) : créer un widget et récupérer les deux clés.
4. Déposer par FTP un fichier `.env` à la racine du site, à partir de `.env.example`
   (`APP_KEY` générée avec `php artisan key:generate --show`, base de données, SMTP Brevo,
   `BREVO_API_KEY`, `DEPLOY_TOKEN` = `openssl rand -hex 32`, `ADMIN_EMAIL`).
5. **GitHub** > Settings > Secrets and variables > Actions : `FTP_SERVER`, `FTP_USERNAME`,
   `FTP_PASSWORD`, `FTP_SERVER_DIR`, `DEPLOY_URL` (https://saint-joseph.saint-arnoux.fr), `DEPLOY_TOKEN`.
6. Pousser sur `main` : l'action GitHub teste, compile, envoie par FTP puis lance les migrations.

Ensuite, chaque push sur `main` déploie automatiquement.
