FROM php:8.2-fpm

# Allow host UID/GID override (defaults to 33 for www-data)
ARG UID=33
ARG GID=33

# ----------------------------------------------------------
# 1. Install system dependencies (with sudo + nano)
# ----------------------------------------------------------
RUN apt-get update -y && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    nano \
    sudo \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------
# 2. Match www-data UID/GID with host for file permissions
# ----------------------------------------------------------
RUN if getent group www-data >/dev/null 2>&1; then \
        groupmod -g ${GID} www-data; \
    else \
        addgroup --gid ${GID} www-data; \
    fi && \
    if id www-data >/dev/null 2>&1; then \
        usermod -u ${UID} www-data; \
    else \
        adduser --disabled-password --gecos "" --uid ${UID} --gid ${GID} www-data; \
    fi

# ----------------------------------------------------------
# 3. Allow www-data to use sudo (no password)
# ----------------------------------------------------------
RUN usermod -aG sudo www-data && \
    echo "www-data ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# ----------------------------------------------------------
# 4. Set correct ownership for /var/www (for .aws, etc.)
# ----------------------------------------------------------
RUN mkdir -p /var/www && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www

# ----------------------------------------------------------
# 5. Install Composer & AWS CLI
# ----------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" \
    && unzip awscliv2.zip \
    && ./aws/install \
    && rm -rf aws awscliv2.zip

# ----------------------------------------------------------
# 6. Setup working directory and entrypoint
# ----------------------------------------------------------
WORKDIR /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]

# ----------------------------------------------------------
# 7. Copy app files and fix permissions
# ----------------------------------------------------------
COPY --chown=www-data:www-data . .

RUN mkdir -p bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap storage \
    && chmod -R 775 bootstrap storage

# ----------------------------------------------------------
# 8. Switch to non-root user for runtime
# ----------------------------------------------------------
USER www-data

# ----------------------------------------------------------
# 9. Install PHP dependencies (as www-data)
# ----------------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------------
# 10. Expose port and start PHP-FPM
# ----------------------------------------------------------
EXPOSE 9000
CMD ["php-fpm"]
