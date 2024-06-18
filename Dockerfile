FROM php:7.4-bullseye

RUN apt-get update && apt-get install -y zip sqlite3 libsqlite3-dev git jq

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite pcntl
RUN pecl install xdebug-3.1.0 \
    && echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20190902/xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /tsqm-php

CMD tail -f /dev/null