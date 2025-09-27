# Use PHP with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install required PHP extensions for Laravel and SQLite + sqlite3 CLI
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    curl \
    git \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . /var/www/html

# Install Composer CLI
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Laravel dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set Apache DocumentRoot to /public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

# Fix permissions for Laravel and SQLite
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && find /var/www/html/database -type f -name "*.db" -exec chmod 664 {} \;

# Expose Apache port
EXPOSE 80

# Run migrations automatically before starting Apache
CMD php artisan migrate --force && apache2-foreground