FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    autoconf \
    bash \
    curl \
    freetype-dev \
    g++ \
    gcc \
    git \
    icu-dev \
    imagemagick-dev \
    libc-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    make \
    mysql-client \
    nodejs \
    npm \
    oniguruma-dev \
    openssh-client \
    rsync \
    zlib-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    mysqli \
    opcache \
    pdo_mysql \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install ImageMagick extension
RUN pecl install imagick && docker-php-ext-enable imagick

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

    # Create www-data user with proper UID/GID
    RUN adduser -D -s /bin/sh -u 1000 -G www-data www-data# Set working directory
WORKDIR /var/www/html

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy application files
COPY mvp/ /var/www/html/
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x /usr/local/bin/entrypoint.sh

# Install Composer dependencies
RUN if [ -f composer.json ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

# Expose port
EXPOSE 9000

# Switch to www-data user
USER www-data

# Start PHP-FPM
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
