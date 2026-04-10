FROM php:8.3-fpm

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libavif-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    libmagickwand-dev \
    ghostscript \
    msmtp \
    msmtp-mta \
    unzip \
    curl \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-avif \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo \
    pdo_mysql \
    zip \
    intl \
    xml \
    mbstring \
    exif \
    opcache \
    bcmath

# ImageMagick para SVG
RUN pecl install imagick && docker-php-ext-enable imagick

# Redis (opcional, útil para object cache)
RUN pecl install redis && docker-php-ext-enable redis

# WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Configuração msmtp para enviar emails via Mailpit
RUN echo "defaults\n\
host mailpit\n\
port 1025\n\
tls off\n\
tls_starttls off\n\
" > /etc/msmtprc

# PHP ini customizado (baked na imagem, pode ser sobrescrito por volume mount)
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Permissões
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/sites

EXPOSE 9000
CMD ["php-fpm"]
