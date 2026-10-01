FROM php:8.5-fpm-alpine AS php-base
RUN apk add --no-cache nginx supervisor icu-libs libzip libpq \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev \
    && docker-php-ext-install -j2 intl zip pdo_mysql pdo_pgsql pcntl \
    && apk del .build-deps
WORKDIR /var/www/html

FROM php-base AS build
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && npm ci \
    && npm run build \
    && rm -rf node_modules bootstrap/cache/*.php

FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning \
    SESSION_SECURE_COOKIE=true SESSION_HTTP_ONLY=true SESSION_SAME_SITE=lax \
    MARKETPLACE_DEMO=false STRIPE_LIVE=false PORT=8080
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY deploy/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY deploy/supervisord.conf /etc/supervisord.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/production.ini
COPY deploy/entrypoint.sh /usr/local/bin/colisfirst-start
RUN chmod +x /usr/local/bin/colisfirst-start
EXPOSE 8080
ENTRYPOINT ["colisfirst-start"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
