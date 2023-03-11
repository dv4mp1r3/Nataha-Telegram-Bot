FROM php:7.4-fpm-alpine3.12 as php_74
RUN apk add --no-cache $PHPIZE_DEPS git \
    && pecl install xdebug-3.1.6 redis \
    && docker-php-ext-enable xdebug redis \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-configure sockets --enable-sockets \
<<<<<<<< HEAD:configs/php74.dockerfile
    && docker-php-ext-install pcntl sockets \
    && apk add --no-cache \
========
    && docker-php-ext-install pcntl sockets mysqli pdo pdo_mysql bcmath
RUN apk add --no-cache \
>>>>>>>> 1407b20dc89646ef88f32253e567eb60c1384393:php74.dockerfile
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

FROM php_74 as composer_74
WORKDIR /var/www
COPY --from=composer/composer /usr/bin/composer /usr/bin/composer