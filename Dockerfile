FROM php:8.1-cli AS build

WORKDIR /build

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.1-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gettext \
        ssl-cert \
    && curl -fsSL --retry 5 --retry-delay 2 \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        imagick \
        bcmath \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        redis \
    && a2enmod ssl rewrite \
    && make-ssl-cert generate-default-snakeoil --force-overwrite \
    && { \
        echo 'post_max_size=100M'; \
        echo 'upload_max_filesize=100M'; \
        echo 'max_execution_time=600'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'memory_limit=512M'; \
    } > /usr/local/etc/php/conf.d/lsky.ini \
    && mkdir -p /var/www/data /var/www/lsky /var/www/html \
    && rm -rf /var/lib/apt/lists/*

COPY --from=build /build /var/www/lsky/
COPY 000-default.conf.template /000-default.conf.template
COPY ports.conf.template /ports.conf.template
COPY entrypoint.sh /entrypoint.sh

WORKDIR /var/www/html/

VOLUME ["/var/www/html"]

ENV WEB_PORT=8089
ENV HTTPS_PORT=8088

EXPOSE 8089
EXPOSE 8088

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apachectl", "-D", "FOREGROUND"]