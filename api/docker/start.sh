#!/bin/sh
set -eu

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p data
php scripts/init-db.php

exec frankenphp php-server --listen 0.0.0.0:8080 --root /app/public
