FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    bash \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libxml2-dev \
    netcat-openbsd \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application files
COPY . .
COPY --from=assets-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Prepare entrypoint script (run migrations, prepare data, etc..)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000
EXPOSE 9000

# Use the entrypoint script to run migrations and start the server
ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]
