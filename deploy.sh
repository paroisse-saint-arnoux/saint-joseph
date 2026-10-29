#!/usr/bin/env bash
# Déploiement sur le serveur (O2Switch) depuis la branche `production` construite par la GitHub Action.
#   ./deploy.sh           ne fait rien si le serveur est déjà à jour (utilisable en cron)
#   ./deploy.sh --force   relance tout (composer, migrations, caches) même sans nouveau commit
# Variables facultatives : PHP (binaire PHP 8.5, défaut `php`), COMPOSER (défaut `composer`).
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
BRANCH=production

# Un seul déploiement à la fois (cron + lancement manuel).
exec 9>storage/deploy.lock
flock -n 9 || { echo "Déploiement déjà en cours."; exit 0; }

git fetch -q origin "$BRANCH"
if [ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$BRANCH")" ] && [ "${1:-}" != "--force" ]; then
    exit 0
fi

echo "[$(date '+%F %T')] Déploiement de $(git log -1 --format='%h %s' "origin/$BRANCH")"

# Page de maintenance pendant la mise à jour, levée même en cas d'erreur.
"$PHP" artisan down --retry=15 || true
trap '"$PHP" artisan up' EXIT

# Les fichiers ignorés par git (.env, vendor, storage, uploads) ne sont pas touchés.
git checkout -q -f -B "$BRANCH" "origin/$BRANCH"
"$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
"$PHP" artisan migrate --force
[ -L public/storage ] || "$PHP" artisan storage:link
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize

echo "[$(date '+%F %T')] Terminé."
