FROM php:8.4-fpm-alpine

# Build args to align container user with host user (avoids permission issues on Linux).
ARG UID=1000
ARG GID=1000

# System dependencies needed to build PHP extensions.
RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        libpng-dev \
        postgresql-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        gd \
        bcmath \
        opcache \
        pcntl \
    # Redis extension (phpredis) via PECL.
    && pecl install redis \
    && docker-php-ext-enable redis \
    # PCOV extension (fast, coverage-only) via PECL.
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    # Clean up build deps to keep the image small.
    && apk del $PHPIZE_DEPS

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Custom PHP config.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Create a non-root user matching the host UID/GID.
# Reuse the group if the GID already exists in the base image (e.g. GID 20 on macOS).
RUN set -eux; \
    if getent group "${GID}" >/dev/null; then \
        groupname="$(getent group "${GID}" | cut -d: -f1)"; \
    else \
        addgroup -g "${GID}" app; \
        groupname=app; \
    fi; \
    adduser -D -u "${UID}" -G "${groupname}" app

WORKDIR /var/www/html

USER app

EXPOSE 9000

CMD ["php-fpm"]
