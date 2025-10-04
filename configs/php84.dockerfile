#FROM php:8.4.12-fpm-alpine3.21 as php_84_build
FROM php:7.4-fpm-alpine3.12 as php_84_build
RUN apk add --no-cache $PHPIZE_DEPS git linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-configure sockets --enable-sockets \
    && docker-php-ext-install pcntl sockets mysqli pdo pdo_mysql bcmath \
    && apk add --no-cache \
      freetype \
      sqlite \
      libjpeg-turbo \
      libpng \
      freetype-dev \
      sqlite-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \
      --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd pdo_sqlite \
    && docker-php-ext-enable gd \
    && apk del --no-cache \
      freetype-dev \
      sqlite-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    #&& pecl channel-update pecl.php.net && pecl install xdebug-3.4.5 \
    && pecl channel-update pecl.php.net && pecl install xdebug-3.1.6 \
    && rm -rf /tmp/*

#FROM php:8.4.12-fpm-alpine3.21 as php_84_base
FROM php:7.4-fpm-alpine3.12 as php_84_base

RUN apk add --no-cache \
      freetype \
      sqlite \
      libjpeg-turbo \
      libpng

COPY --from=php_84_build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php_84_build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=composer:2.7.1 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

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


FROM php_84_base as php_84_prod
COPY --chown=web:web ./php /var/www
RUN composer install --no-dev


FROM php_84_base as php_84_dev
USER root
RUN docker-php-ext-enable xdebug
USER web