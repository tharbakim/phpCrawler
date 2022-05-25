FROM php:8-apache

RUN a2enmod rewrite
COPY ./web /var/www/html
COPY ./.env ./web/.env
COPY ./crawler/001-crawler.conf /etc/apache2/sites-available/001-crawler.conf
COPY ./crawler/ports.conf /etc/apache2/ports.conf
RUN chown -R www-data:www-data /var/www/html && a2ensite 001-crawler.conf && a2dissite 000-default.conf