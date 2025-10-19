#!/usr/bin/env sh
set -euo pipefail

echo "[entrypoint] starting buna-jebena (APP_ENV=${APP_ENV:-local})"

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
catch(Throwable $e){ fwrite(STDERR, "DB not ready: ".$e->getMessage()."\n"); exit(1); }' \
; do
  tries=$((tries+1))
  if [ $tries -ge 30 ]; then
    echo "[entrypoint] DB still not ready after $tries attempts, exiting"
    exit 1
  fi
  sleep 2
done
echo "[entrypoint] DB ready"

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

# --- run migrations (safe + idempotent) ---
php artisan migrate --force || {
  echo "[entrypoint] migrations failed"; exit 1;
}

echo "[entrypoint] starting php-fpm"
exec php-fpm -F
