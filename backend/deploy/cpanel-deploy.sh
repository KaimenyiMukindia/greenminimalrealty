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
exec >>"$LOG_FILE" 2>&1

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

for command_name in php composer curl tar sha256sum; do
    command -v "$command_name" >/dev/null 2>&1 || { log "Missing required executable: $command_name"; exit 127; }
done
[[ -f "$SETTINGS_FILE" ]] || { log "Missing private production settings: $SETTINGS_FILE"; exit 1; }

log "PHP runtime: $(php -r 'echo PHP_VERSION;')"

REVISION="$(git -C "$REPO_ROOT" rev-parse HEAD)"
RELEASE_URL="https://github.com/KaimenyiMukindia/greenminimalrealty/releases/download/nuxt-$REVISION"
RELEASE_DIR="$(mktemp -d "$HOME/.gmr-release.XXXXXXXX")"
cleanup_release() {
    rm -f "$RELEASE_DIR/nuxt.tar.gz" "$RELEASE_DIR/nuxt.tar.gz.sha256"
    rmdir "$RELEASE_DIR"
}
trap cleanup_release EXIT
log "Download the Linux Nuxt build for $REVISION before modifying live files"
run curl --fail --location --retry 3 "$RELEASE_URL/nuxt.tar.gz" -o "$RELEASE_DIR/nuxt.tar.gz"
run curl --fail --location --retry 3 "$RELEASE_URL/nuxt.tar.gz.sha256" -o "$RELEASE_DIR/nuxt.tar.gz.sha256"
[[ "$(cat "$RELEASE_DIR/nuxt.tar.gz.sha256")" =~ ^[a-f0-9]{64}\ \ nuxt\.tar\.gz$ ]] || { log 'Invalid build checksum manifest'; exit 1; }
(cd "$RELEASE_DIR" && sha256sum -c nuxt.tar.gz.sha256)
[[ "$(tar -xOf "$RELEASE_DIR/nuxt.tar.gz" ./revision)" == "$REVISION" ]] || { log 'Nuxt build revision does not match HEAD'; exit 1; }
tar -tzf "$RELEASE_DIR/nuxt.tar.gz" | grep -Fx './server/index.mjs' >/dev/null

mkdir -p "$DEPLOYPATH/backend" "$DEPLOYPATH/frontend"

log 'Sync backend runtime files (Markdown, docs, tests, secrets, local SQLite and caches excluded)'
tar -C "$REPO_ROOT/backend" \
        --exclude='*.md' \
        --exclude='.env*' \
        --exclude='.claude' \
        --exclude='.mcp.json' \
        --exclude='.git' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='tests' \
        --exclude='phpunit.xml' \
        --exclude='database/database.sqlite' \
        --exclude='./storage/framework/*' \
        --exclude='./storage/logs/*' \
        --exclude='./bootstrap/cache/*' \
        -cf - . | tar -C "$DEPLOYPATH/backend" -xf -
log 'Backend sync complete'

log 'Sync frontend runtime files (Markdown, secrets, Git and build caches excluded)'
tar -C "$REPO_ROOT/frontend" \
        --exclude='*.md' \
        --exclude='.env*' \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='.nuxt' \
        --exclude='.output' \
        -cf - . | tar -C "$DEPLOYPATH/frontend" -xf -
log 'Frontend sync complete'

log 'Remove source documentation, developer tools, PHPUnit tests and local SQLite from live deployment'
rm -rf "$DEPLOYPATH/backend/.claude" "$DEPLOYPATH/backend/tests" "$DEPLOYPATH/frontend/.git"
rm -f "$DEPLOYPATH/backend/.mcp.json" "$DEPLOYPATH/backend/phpunit.xml" "$DEPLOYPATH/backend/database/database.sqlite"
find "$DEPLOYPATH/backend" -type f -name '*.md' ! -path "$DEPLOYPATH/backend/vendor/*" -delete
find "$DEPLOYPATH/frontend" -type f -name '*.md' ! -path "$DEPLOYPATH/frontend/node_modules/*" ! -path "$DEPLOYPATH/frontend/.output/*" -delete
log 'Source-only files removed from live deployment'

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
run php "$DEPLOYPATH/backend/artisan" config:clear
run php "$DEPLOYPATH/backend/artisan" migrate --force
run php "$DEPLOYPATH/backend/artisan" optimize:clear

if [[ ! -e "$DEPLOYPATH/backend/public/storage" ]]; then
    run ln -s "$DEPLOYPATH/backend/storage/app/public" "$DEPLOYPATH/backend/public/storage"
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
if [[ -f "$DEPLOYPATH/.htaccess" ]]; then
    run cp "$DEPLOYPATH/.htaccess" "$DEPLOYPATH/.htaccess-backup-$(date -u '+%Y%m%d%H%M%S')-$$"
fi
run cp "$REPO_ROOT/backend/deploy/site.htaccess" "$DEPLOYPATH/.htaccess"
log 'Publish the new Nuxt build; retain the previous output for rollback'
OUTPUT_STAGE="$(mktemp -d "$DEPLOYPATH/frontend/.output-release.XXXXXXXX")"
run tar -C "$OUTPUT_STAGE" -xzf "$RELEASE_DIR/nuxt.tar.gz"
if [[ -e "$DEPLOYPATH/frontend/.output" ]]; then
    OUTPUT_BACKUP="$DEPLOYPATH/frontend/.output-backup-$(date -u '+%Y%m%d%H%M%S')-$$"
    run mv "$DEPLOYPATH/frontend/.output" "$OUTPUT_BACKUP"
fi
run mv "$OUTPUT_STAGE" "$DEPLOYPATH/frontend/.output"
run php "$DEPLOYPATH/backend/artisan" queue:restart

log 'SUCCESS: source synced, schema current, fresh Nuxt production build deployed'
log 'Restart the Nuxt Node process to load the new build and environment'
log "Deployment log: $LOG_FILE"
