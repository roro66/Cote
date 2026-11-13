# Producción: la imagen de Sail atendiendo con PHP-FPM detrás de nginx, en vez del servidor de desarrollo.
FROM sail-8.4/app:latest

USER root

RUN rm -f /etc/apt/sources.list.d/*yarn* \
    && apt-get update \
    && apt-get install -y --no-install-recommends php8.4-fpm \
    && apt-get install -y --only-upgrade $(dpkg-query -W -f='${db:Status-Abbrev} ${Package}\n' 'php8.4-*' | awk '$1 == "ii" { print $2 }') \
    && apt-get purge -y php8.4-xdebug php8.4-pcov \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir -p /run/php \
    && sed -i 's|^listen = .*|listen = 9000|' /etc/php/8.4/fpm/pool.d/www.conf \
    && sed -i 's/^user = www-data/user = sail/; s/^group = www-data/group = sail/' /etc/php/8.4/fpm/pool.d/www.conf \
    && sed -i 's/^;clear_env = no/clear_env = no/' /etc/php/8.4/fpm/pool.d/www.conf \
    && sed -i 's/^;catch_workers_output = yes/catch_workers_output = yes/' /etc/php/8.4/fpm/pool.d/www.conf \
    && sed -i 's|^error_log = .*|error_log = /proc/self/fd/2|' /etc/php/8.4/fpm/php-fpm.conf \
    && cp /etc/php/8.4/cli/conf.d/99-sail.ini /etc/php/8.4/fpm/conf.d/99-sail.ini \
    && printf 'memory_limit = 512M\n' > /etc/php/8.4/fpm/conf.d/99-cote.ini

ENV SUPERVISOR_PHP_COMMAND="/usr/sbin/php-fpm8.4 --nodaemonize --fpm-config /etc/php/8.4/fpm/php-fpm.conf"
ENV SUPERVISOR_PHP_USER="root"

EXPOSE 9000
