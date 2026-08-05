FROM php:8.2-apache

# Installer les dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl pdo pdo_pgsql zip opcache gd

# Configurer PHP pour accepter des gros fichiers (photos)
RUN echo "upload_max_filesize = 20M\npost_max_size = 25M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

# Activer le module rewrite d'Apache (nécessaire pour Symfony)
RUN a2enmod rewrite

# Configuration du DocumentRoot (Symfony pointe sur le dossier /public)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Autoriser le .htaccess pour Symfony
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Installer Composer (gestionnaire de paquets PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code source de l'application dans le conteneur
COPY . .

# Installer les dépendances Symfony (optimisé pour la production)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Compiler les assets pour la production
ENV APP_ENV=prod
RUN php bin/console asset-map:compile

# Donner les bonnes permissions aux dossiers de cache et logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ public/
