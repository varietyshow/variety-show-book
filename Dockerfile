# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache rewrite module (useful if you use .htaccess)
RUN a2enmod rewrite

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install system dependencies for Composer (git, unzip)
RUN apt-get update && apt-get install -y git unzip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory to Apache's default web root
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Install PHP dependencies via Composer
RUN composer install --no-dev --optimize-autoloader

# Optional: set correct permissions (depending on your needs)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80
