#!/usr/bin/env bash
# Mise à jour du site sur le serveur (O2Switch), à lancer en SSH depuis le dossier du site :
#   ./deploy.sh
# Le serveur suit la branche main. Le front compilé (public/build) est versionné sur main : pas de npm sur le serveur.
# Variables facultatives : PHP (binaire PHP 8.5, défaut `php`), COMPOSER (défaut `composer`).
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

# Sans clé d'application, toutes les pages répondent 500 : on s'arrête avant de mettre la config en cache.
grep -qE '^APP_KEY=.+' .env || { echo "APP_KEY manquante dans .env : lancer « php artisan key:generate --force » puis relancer ./deploy.sh."; exit 1; }

echo "[$(date '+%F %T')] Mise à jour depuis main"

# Page de maintenance pendant la mise à jour, levée même en cas d'erreur.
"$PHP" artisan down --retry=15 || true
trap '"$PHP" artisan up' EXIT

# Les fichiers ignorés par git (.env, vendor, storage, uploads) ne sont pas touchés.
git pull --ff-only origin main
"$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
"$PHP" artisan migrate --force
[ -L public/storage ] || "$PHP" artisan storage:link
# Clés OAuth (connecteur Claude, jetons API) : générées une fois, hors git.
[ -f storage/oauth-private.key ] || "$PHP" artisan passport:keys
# Copies d'images de la bibliothèque inutilisées depuis plus d'un jour.
"$PHP" artisan bibliotheque:nettoyer
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize

echo "[$(date '+%F %T')] Terminé : $(git log -1 --format='%h %s')"
