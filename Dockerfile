# Imagen base PHP 8.4 con Apache
FROM php:8.4-apache

# Paquetes necesarios
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    libpq-dev \
    libz-dev \
    git \
    curl \
    vim \
    unzip \
    libxml2-dev \
    libssl-dev \
    autoconf \
    gcc \
    make \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql intl

# Instalamos y habilitamos Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Habilita mod_rewrite
RUN a2enmod rewrite

# DocumentRoot de Symfony
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/* \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf

# Copiamos apache2.conf modificado para AllowOverride All (opcional)
COPY config/apache2/apache2.conf /etc/apache2/apache2.conf

# Copiamos la aplicación
COPY . /var/www/html/

# Permisos y dependencias PHP
WORKDIR /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php \
    && php composer.phar install \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/var

# Copiamos configuración personalizada de Xdebug
COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Puerto HTTP
EXPOSE 80

# Iniciamos Apache
CMD ["apache2-foreground"]
