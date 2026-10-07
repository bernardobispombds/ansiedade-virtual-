FROM php:8.3-apache

# Driver do PostgreSQL para o PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Faz o Apache executar PHP também dentro dos arquivos .html
# (assim você não precisa renomear as páginas nem mudar os links)
COPY docker/html-php.conf /etc/apache2/conf-available/html-php.conf
RUN a2enconf html-php

COPY . /var/www/html/
