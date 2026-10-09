FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
COPY includes/ /var/www/includes/
COPY public/ /var/www/html/

ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "port=${PORT:-10000}; sed -ri \"s/Listen 80/Listen ${port}/\" /etc/apache2/ports.conf; sed -ri \"s/:80>/:${port}>/\" /etc/apache2/sites-enabled/000-default.conf; exec apache2-foreground"]
