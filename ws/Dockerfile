FROM phpswoole/swoole:php8.3-alpine

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/ws
COPY . .

RUN composer install

EXPOSE 9502

CMD [ "php", "src/index.php" ]
