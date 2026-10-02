#!/usr/bin/env bash
set -Eeuo pipefail

REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
DEPLOYPATH="/home/lrnzwljz/gmr.nyimuki.com"
SETTINGS_FILE="$HOME/.gmr-production.env"
LOG_DIR="$HOME/logs"
LOG_FILE="$LOG_DIR/gmr-deploy.log"
SEED_MARKER="$DEPLOYPATH/.initial-content-seeded"

mkdir -p "$LOG_DIR"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"
exec > >(tee -a "$LOG_FILE") 2>&1

log() {
    printf '[%s] %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$*"
}

fail() {
    status=$?
    log "FAILED at line ${BASH_LINENO[0]:-unknown} (exit ${status})"
    log "Full deployment output: $LOG_FILE"
    exit "$status"
}
trap fail ERR

run() {
    log "RUN: $*"
    "$@"
    log 'OK'
}

log "START commit=$(git -C "$REPO_ROOT" rev-parse --short HEAD) repo=$REPO_ROOT deploy=$DEPLOYPATH"

for command_name in rsync php composer npm node tee; do
    command -v "$command_name" >/dev/null 2>&1 || { log "Missing required executable: $command_name"; exit 127; }
done
[[ -f "$SETTINGS_FILE" ]] || { log "Missing private production settings: $SETTINGS_FILE"; exit 1; }

log "Runtime versions: $(php -r 'echo PHP_VERSION;') / $(node --version) / $(npm --version)"

mkdir -p "$DEPLOYPATH/backend" "$DEPLOYPATH/frontend"

log 'Sync backend runtime files (Markdown, docs, tests, secrets, local SQLite and caches excluded)'
rsync -a \
    --exclude='*.md' \
    --exclude='.env*' \
    --exclude='.claude/' \
    --exclude='.mcp.json' \
    --exclude='.git/' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='tests/' \
    --exclude='phpunit.xml' \
    --exclude='database/database.sqlite' \
    --exclude='storage/framework/' \
    --exclude='storage/logs/' \
    "$REPO_ROOT/backend/" "$DEPLOYPATH/backend/"
log 'Backend sync complete'

log 'Sync frontend runtime files (Markdown, secrets, Git and build caches excluded)'
rsync -a \
    --exclude='*.md' \
    --exclude='.env*' \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='.nuxt/' \
    --exclude='.output/' \
    "$REPO_ROOT/frontend/" "$DEPLOYPATH/frontend/"
log 'Frontend sync complete'

log 'Generate Laravel and Nuxt environment files from private server settings'
set -a
# shellcheck disable=SC1090
source "$SETTINGS_FILE"
set +a
php "$DEPLOYPATH/backend/deploy/generate-production-env.php" \
    "$DEPLOYPATH/backend/.env" \
    "$DEPLOYPATH/frontend/.env"
log 'Environment files generated (values are not written to deployment log)'

run composer install --working-dir="$DEPLOYPATH/backend" --no-dev --prefer-dist --optimize-autoloader --no-interaction
run php "$DEPLOYPATH/backend/artisan" optimize:clear
run php "$DEPLOYPATH/backend/artisan" migrate --force

if [[ ! -e "$DEPLOYPATH/backend/public/storage" ]]; then
    run php "$DEPLOYPATH/backend/artisan" storage:link
else
    log 'Laravel public/storage link already exists'
fi

if [[ ! -f "$SEED_MARKER" ]]; then
    log 'Initial content seed has not run; seeding database once'
    run php "$DEPLOYPATH/backend/artisan" db:seed --force
    touch "$SEED_MARKER"
    log "Initial seed complete; marker created: $SEED_MARKER"
else
    log "Initial seed skipped; marker exists: $SEED_MARKER"
fi

run php "$DEPLOYPATH/backend/artisan" config:cache
run npm --prefix "$DEPLOYPATH/frontend" ci --include=dev
run npm --prefix "$DEPLOYPATH/frontend" run build

log 'SUCCESS: source synced, schema current, Nuxt production build generated'
log "Deployment log: $LOG_FILE"
