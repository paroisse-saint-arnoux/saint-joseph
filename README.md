# Site Saint Joseph (Le Rouret)

Site du projet de centre paroissial et d'église Saint Joseph, porté par la Paroisse Saint-Arnoux
(Association Diocésaine de Nice). Production : https://saint-joseph.saint-arnoux.fr/

Laravel 13 · Filament 5 (administration) · SCSS + Bootstrap · MySQL · hébergé sur O2Switch.
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

1. **cPanel O2Switch** : créer la base MySQL et son utilisateur ; faire pointer la racine du
   sous-domaine `saint-joseph.saint-arnoux.fr` vers le dossier `…/public` du site.
2. **Brevo** : régénérer la clé API, créer une clé SMTP, authentifier le domaine `saint-arnoux.fr` (SPF/DKIM).
3. **Cloudflare Turnstile** (facultatif) : créer un widget et récupérer les deux clés.
4. **Serveur (SSH)** : cloner le dépôt dans le dossier du site, puis créer le `.env` à partir de
   `.env.example` (`APP_KEY` générée avec `php artisan key:generate --show`, base de données,
   SMTP Brevo, `BREVO_API_KEY`, `ADMIN_EMAIL`).
5. Pousser sur `main` : l'action GitHub teste, compile le front et met à jour la branche `production`.
6. Sur le serveur : `./deploy.sh` (bascule sur `production`, Composer, migrations, caches).
   Pour automatiser : cron cPanel toutes les 5 minutes,
   `cd ~/DOSSIER_DU_SITE && ./deploy.sh >> storage/logs/deploy.log 2>&1` (ne fait rien s'il n'y a pas de nouveauté).

Ensuite, chaque push sur `main` est mis en ligne au prochain `./deploy.sh` (ou passage du cron).
