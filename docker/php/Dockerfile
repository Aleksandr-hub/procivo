FROM php:8.4-fpm

ARG XDEBUG=false

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        intl \
        opcache \
        zip \
        gd \
        bcmath \
        sockets

# PECL extensions
RUN pecl install redis amqp \
    && docker-php-ext-enable redis amqp

# Xdebug (optional)
RUN if [ "$XDEBUG" = "true" ]; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www

# Run as non-root
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
USER www-data
