# BACKEND
# Starte ein php 8.2 + apache image ( apacha liefert php dateien aus un d kann urls via rewrite umleiten)
FROM php:8.2-apache

WORKDIR /var/www/html/
# installiere benötigte erweiterungen für php, pdo= datenbankzugriff und pdo_mysql= mysql support
RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY . .

RUN chown -R www-data:www-data /var/www/html
# apache läfut auf port 80
EXPOSE 80
CMD ["apache2-foreground"]