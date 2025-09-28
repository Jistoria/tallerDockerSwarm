FROM php:8.3-cli-alpine

# Install PostgreSQL client libraries, dev packages, and curl
RUN apk add --no-cache postgresql-dev curl

# pdo_pgsql
RUN docker-php-ext-install pdo pdo_pgsql

WORKDIR /app
COPY composer.json /app/
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php \
 && composer install --no-dev --prefer-dist

COPY . /app

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
