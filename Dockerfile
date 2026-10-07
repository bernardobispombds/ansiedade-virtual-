FROM php:8.3-apache

# Driver do PostgreSQL para o PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Faz o Apache executar PHP também dentro dos arquivos .html
RUN printf '%s\n' '<FilesMatch "\.html$">' '    SetHandler application/x-httpd-php' '</FilesMatch>' > /etc/apache2/conf-available/html-php.conf \
    && a2enconf html-php

COPY . /var/www/html/
