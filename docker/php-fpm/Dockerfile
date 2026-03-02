FROM php:8.4-fpm

WORKDIR /application

RUN apt-get update \
        && apt-get install -y --no-install-recommends \
        libicu-dev \
        zlib1g-dev \
        libpq-dev \
        libzip-dev \
        libpcre2-dev \
        libonig-dev \
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
        sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# pgsql and pdo_pgsql already installed above

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

# Install Node.js dependencies (puppeteer-extra + stealth plugin for CRECI SP scraper)
COPY package.json package-lock.json /application/
RUN npm ci --omit=dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie o arquivo php.ini para o container
COPY ./docker/php-fpm/php-ini-overrides.ini /usr/local/etc/php/php.ini

# Exponha a porta 9000 para o PHP-FPM
EXPOSE 9000

# Defina o comando de inicialização para o PHP-FPM
CMD ["php-fpm"]