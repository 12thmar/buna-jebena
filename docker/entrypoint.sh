#!/usr/bin/env sh
set -euo pipefail

echo "[entrypoint] starting buna-jebena (APP_ENV=${APP_ENV:-local})"

<<<<<<< HEAD
=======
# --- ensure composer exists ---
if ! command -v composer >/dev/null 2>&1; then
  echo "[entrypoint] ERROR: composer not found in PATH. Install composer in the image or use a composer stage."
  exit 1
fi
export COMPOSER_ALLOW_SUPERUSER=1

>>>>>>> add-contact
# --- wait for MySQL (no mysql client needed) ---
echo "[entrypoint] waiting for DB ${DB_HOST:-db}:${DB_PORT:-3306} ..."
tries=0
until php -r '
$h=getenv("DB_HOST") ?: "db";
$p=(int)(getenv("DB_PORT") ?: 3306);
$u=getenv("DB_USERNAME") ?: "bj";
$pw=getenv("DB_PASSWORD") ?: "bjpass";
$d=getenv("DB_DATABASE") ?: "bj";
try { new PDO("mysql:host=$h;port=$p;dbname=$d;charset=utf8mb4",$u,$pw,[PDO::ATTR_TIMEOUT=>2]); exit(0); }
<<<<<<< HEAD
catch(Throwable $e){ fwrite(STDERR, "DB not ready: ".$e->getMessage()."\n"); exit(1); }' \
; do
=======
catch(Throwable $e){ fwrite(STDERR, "DB not ready: ".$e->getMessage()."\n"); exit(1); }'
do
>>>>>>> add-contact
  tries=$((tries+1))
  if [ $tries -ge 30 ]; then
    echo "[entrypoint] DB still not ready after $tries attempts, exiting"
    exit 1
  fi
  sleep 2
done
echo "[entrypoint] DB ready"

<<<<<<< HEAD
=======
# --- install composer deps if vendor missing (bind mounts will hide baked vendor) ---
if [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] vendor/ missing — running 'composer install'..."
  composer install --no-interaction --prefer-dist --no-progress
fi

>>>>>>> add-contact
# --- ensure .env exists ---
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
  echo "[entrypoint] created .env from example"
fi

# --- app key (only if missing) ---
if ! php -r 'exit((getenv("APP_KEY") ?: (preg_match("/^APP_KEY=.+/m", file_exists(".env")?file_get_contents(".env"):"")?1:0))?0:1);'; then
  php artisan key:generate --force || true
  echo "[entrypoint] generated APP_KEY"
fi

# --- environment-specific optimizations ---
if [ "${APP_ENV:-local}" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
else
  php artisan optimize:clear || true
fi

<<<<<<< HEAD
# --- run migrations (safe + idempotent) ---
php artisan migrate --force || {
  echo "[entrypoint] migrations failed"; exit 1;
}
=======
# --- ensure required Laravel runtime dirs exist and are writable ---
echo "[entrypoint] ensuring storage and cache dirs..."
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# --- run migrations (safe + idempotent) ---
php artisan migrate --force || { echo "[entrypoint] migrations failed"; exit 1; }
>>>>>>> add-contact

echo "[entrypoint] starting php-fpm"
exec php-fpm -F
