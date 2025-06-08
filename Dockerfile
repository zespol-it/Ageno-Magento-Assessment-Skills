FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libxslt1-dev \
    libonig-dev \
    libmcrypt-dev \
    libmagickwand-dev \
    libpq-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libedit-dev \
    libsqlite3-dev \
    libmemcached-dev \
    zlib1g-dev \
    git \
    unzip \
    cron \
    nano \
    wget \
    gnupg2 \
    lsof \
    libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
        bcmath \
        intl \
        gd \
        pdo_mysql \
        soap \
        xsl \
        zip \
        pcntl \
        opcache \
        sockets \
        mysqli \
        calendar \
        exif \
        sysvsem \
        gettext

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install amqp extension
RUN apt-get update && apt-get install -y librabbitmq-dev && rm -rf /var/lib/apt/lists/* \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Install sodium
RUN docker-php-ext-install sodium

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js & npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@9 && \
    npm install -g grunt-cli && \
    npm install -g yarn

# Set working directory
WORKDIR /var/www/html

# Set recommended PHP settings
COPY php.ini /usr/local/etc/php/

# Set permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

CMD ["php-fpm"] 