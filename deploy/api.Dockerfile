FROM dunglas/frankenphp:php8.4

RUN apt-get update \
  && apt-get install -y --no-install-recommends git unzip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY public ./public
COPY scripts ./scripts
COPY src ./src
COPY views ./views
COPY docker/start.sh /usr/local/bin/start-app

RUN chmod +x /usr/local/bin/start-app

EXPOSE 8080

CMD ["start-app"]
