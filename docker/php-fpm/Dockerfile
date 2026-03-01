FROM php:8.4-fpm

WORKDIR /application

RUN apt-get update \
        && apt-get install -y --no-install-recommends \
        libicu-dev \
        zlib1g-dev \
        libpq-dev \
        libzip-dev \
        libpcre3-dev \
        libcurl4-openssl-dev \
        curl \
        git \
        gnupg \
        && docker-php-ext-install \
        intl \
        mbstring \
        pdo_pgsql \
        pdo \
        pgsql \
        sockets; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && docker-php-ext-install pgsql pdo_pgsql

# Install Node.js (required for CRECI SP headless Chrome scraper)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Google Chrome (required for CRECI SP Enterprise reCAPTCHA)
RUN curl -fsSL https://dl.google.com/linux/linux_signing_key.pub | gpg --dearmor -o /usr/share/keyrings/google-chrome.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome.gpg] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie o arquivo php.ini para o container
COPY ./php-ini-overrides.ini /usr/local/etc/php/php.ini

# Exponha a porta 9000 para o PHP-FPM
EXPOSE 9000

# Defina o comando de inicialização para o PHP-FPM
CMD ["php-fpm"]