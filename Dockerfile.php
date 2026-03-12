FROM wordpress:6.8.2-php8.4-fpm

RUN apt-get update && \
    apt-get install -y rsync && \
    rm -rf /var/lib/apt/lists/*

COPY php/docker-entrypoint.sh /usr/local/bin/
COPY php/php-prod-wordpress.ini /usr/local/etc/php/conf.d/zz-wordpress-custom.ini
COPY php/php-prod.ini /usr/local/etc/php/php.ini
COPY php/fpm-prod.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=www-data:www-data wordpress/wp-content/themes /usr/src/wordpress/wp-content/themes
COPY --chown=www-data:www-data wordpress/plugins-privados /tmp/plugins-privados

RUN chmod -R 755 /var/www/html/wp-content/themes
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
