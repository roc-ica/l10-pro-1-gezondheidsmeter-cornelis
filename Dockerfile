FROM php:8.2-apache

# Install PHP extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy source files
COPY ./src/ /var/www/html/
