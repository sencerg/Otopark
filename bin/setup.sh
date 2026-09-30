#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v brew >/dev/null 2>&1; then
    if [ -x /opt/homebrew/bin/brew ]; then
        eval "$(/opt/homebrew/bin/brew shellenv)"
    else
        echo "Homebrew bulunamadı. Önce Homebrew'u kurun: https://brew.sh" >&2
        exit 1
    fi
fi

echo "==> PHP, PostgreSQL ve Composer kuruluyor"
brew install php postgresql@16 composer
brew link --force --overwrite postgresql@16

echo "==> PostgreSQL servisi başlatılıyor"
brew services start postgresql@16

for _ in {1..20}; do
    pg_isready -q -h 127.0.0.1 && break
    sleep 1
done

[ -f .env ] || cp .env.example .env
set -a; source .env; set +a

echo "==> Veritabanı ve kullanıcı oluşturuluyor"
psql -h 127.0.0.1 -d postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USERNAME}'" | grep -q 1 \
    || psql -h 127.0.0.1 -d postgres -c "CREATE ROLE ${DB_USERNAME} LOGIN PASSWORD '${DB_PASSWORD}'"
psql -h 127.0.0.1 -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_DATABASE}'" | grep -q 1 \
    || psql -h 127.0.0.1 -d postgres -c "CREATE DATABASE ${DB_DATABASE} OWNER ${DB_USERNAME} ENCODING 'UTF8' TEMPLATE template0"

echo "==> Bağımlılıklar, migration ve seed"
composer install --no-interaction
php bin/migrate.php
php bin/seed.php

echo ""
echo "Kurulum tamam. Başlatmak için: composer serve  ->  http://localhost:8000"
