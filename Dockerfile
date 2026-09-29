FROM php:8.5-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libicu-dev \
    libsqlite3-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install -j"$(nproc)" \
    bcmath \
    intl \
    pdo_mysql \
    pdo_sqlite \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_HOME=/tmp/composer

CMD ["php-fpm"]