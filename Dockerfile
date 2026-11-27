# BCP Student Portal - HostForge / Docker image
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo pdo_mysql \
    && a2enmod headers rewrite \
    # Honour the .htaccess files that block config/, src/, database/ and storage/
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
RUN rm -rf /var/www/html/.git /var/www/html/config/local.php \
    && chown -R www-data:www-data /var/www/html/storage

WORKDIR /var/www/html/
ENV PORT=8000
CMD ["sh", "-c", "sed -i \"s/^Listen .*/Listen ${PORT:-8000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:[0-9]*>/<VirtualHost *:${PORT:-8000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
