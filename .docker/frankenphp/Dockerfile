# Tag épinglé : le tag flottant php8.4-alpine a glissé sur PHP 8.5 (2026-11-09),
# la spec du projet fixe PHP 8.4.27.
FROM dunglas/frankenphp:1.13.5-php8.4.27-alpine

RUN install-php-extensions \
    pdo_mysql \
    redis \
    apcu \
    opcache \
    intl \
    gd \
    zip \
    xdebug

ARG USER=appuser
RUN adduser -D $USER && \
    setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp && \
    chown -R $USER:$USER /config/caddy /data/caddy /app
USER $USER
