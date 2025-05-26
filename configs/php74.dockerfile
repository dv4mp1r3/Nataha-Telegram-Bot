FROM php:7.4-fpm-alpine3.12 as php_74_base
RUN apk add --no-cache $PHPIZE_DEPS git \
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
    && rm -rf /tmp/*

RUN addgroup -S web \
    && adduser \
    --disabled-password \
    --gecos "" \
    --home /home/web \
    --ingroup web \
    --uid "1000" \
    web \
    && chown -R web:web /var/www

WORKDIR /var/www
COPY --from=composer:2.7.1 /usr/bin/composer /usr/bin/composer
USER web

FROM php_74_base as php_74_prod
COPY --chown=web:web ./php /var/www
RUN composer install --no-dev


FROM php_74_base as php_74_dev
USER root
RUN pecl channel-update pecl.php.net && pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/* \
USER web