FROM php:8.4-fpm

WORKDIR /application

RUN apt-get update \
        && apt-get install -y --no-install-recommends \
        libicu-dev \
        zlib1g-dev \
        libpq-dev \
        libzip-dev \
        libpcre3-dev \
        git\
        && docker-php-ext-install \
        intl \
        mbstring \
        pdo_pgsql \
        pdo \
        pgsql; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && docker-php-ext-install pgsql pdo_pgsql sockets

# Copie o arquivo php.ini para o container
COPY ./php-ini-overrides.ini /usr/local/etc/php/php.ini

# Exponha a porta 9000 para o PHP-FPM
EXPOSE 9000

# Defina o comando de inicialização para o PHP-FPM
CMD ["php-fpm"]