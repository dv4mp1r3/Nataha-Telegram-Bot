FROM composer:2.7.1 as composer
WORKDIR /var/www
COPY ./php/composer.json ./php/composer.lock ./
RUN composer install --no-dev \
 --ignore-platform-req=ext-sockets \
 --ignore-platform-req=ext-pcntl \
 --ignore-platform-req=ext-gd

FROM php:7.4-fpm-alpine3.12 as php_74
RUN apk add --no-cache $PHPIZE_DEPS git \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-configure sockets --enable-sockets \
    && docker-php-ext-install pcntl sockets \
    && apk add --no-cache \
      freetype \
      libjpeg-turbo \
      libpng \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \
      --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && apk del --no-cache \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && rm -rf /tmp/*

WORKDIR /var/www
COPY ./php /var/www
COPY --from=composer /var/www/vendor /var/www/vendor

RUN addgroup -S web \
    && adduser \
    --disabled-password \
    --gecos "" \
    --home /home/web \
    --ingroup web \
    --uid "1000" \
    web \
    && chown -R web:web /var/www

USER web
