ARG OS_IMAGE=ubuntu:24.04

# CHECK OUT MORE AVAILABLE IMAGES FROM https://hub.docker.com/_/ubuntu
FROM $OS_IMAGE

ARG FRAMELIX_BUILD_VERSION=dev

ENV PHP_VERSION="8.4"
ENV FRAMELIX_APPDATA="/framelix/appdata"
ENV FRAMELIX_USERDATA="/framelix/userdata"
ENV FRAMELIX_SYSTEMDIR="/framelix/system"
ENV FRAMELIX_MODULES=""
ENV FRAMELIX_BUILD_VERSION=$FRAMELIX_BUILD_VERSION

RUN mkdir -p $FRAMELIX_APPDATA $FRAMELIX_SYSTEMDIR /run/php

RUN export DEBIAN_FRONTEND=noninteractive &&  \
    apt update && apt install software-properties-common gnupg curl -y --no-install-recommends

# add node source
RUN  mkdir -p /etc/apt/keyrings && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN NODE_MAJOR=22 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list

RUN export DEBIAN_FRONTEND=noninteractive &&  \
    add-apt-repository ppa:ondrej/php -y &&  \
    add-apt-repository ppa:ondrej/nginx-mainline -y && \
    apt update && \
    apt install ca-certificates cron nginx nodejs php${PHP_VERSION}-xdebug php${PHP_VERSION}-cli php${PHP_VERSION}-fpm php${PHP_VERSION}-common php${PHP_VERSION}-mysql php${PHP_VERSION}-zip php${PHP_VERSION}-gd php${PHP_VERSION}-mbstring php${PHP_VERSION}-curl php${PHP_VERSION}-xml php${PHP_VERSION}-bcmath php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-pgsql 7zip imagemagick git ghostscript nano -y --no-install-recommends && \
    rm /etc/php/*/*/conf.d/*-xdebug.ini && \
    apt upgrade -y

# system and other stuff
COPY docker-build/entrypoint.sh $FRAMELIX_SYSTEMDIR/entrypoint.sh
COPY docker-build/useful-scripts $FRAMELIX_SYSTEMDIR/useful-scripts
COPY docker-build/misc-conf/cronjobs $FRAMELIX_SYSTEMDIR/cronjobs
COPY docker-build/misc-conf/build-image.php $FRAMELIX_SYSTEMDIR/build-image.php
RUN mkdir -p /opt/phpstorm-coverage && chmod 0777 /opt/phpstorm-coverage

# imagemagick
COPY docker-build/misc-conf/imagemagick-policy.xml /etc/ImageMagick-6/policy.xml

# php
COPY docker-build/php-config/php.ini /etc/php/$PHP_VERSION/cli/php.ini
COPY docker-build/php-config/php.ini /etc/php/$PHP_VERSION/fpm/php.ini
COPY docker-build/php-config/fpm-pool.conf $FRAMELIX_SYSTEMDIR/fpm-pool.conf

# nginx
COPY docker-build/nginx-config/nginx.conf /etc/nginx/nginx.conf
COPY docker-build/nginx-config/nginx-ssl.crt $FRAMELIX_SYSTEMDIR/nginx-ssl.crt
COPY docker-build/nginx-config/nginx-ssl.key $FRAMELIX_SYSTEMDIR/nginx-ssl.key
COPY docker-build/nginx-config/snippets /etc/nginx/snippets/framelix
COPY docker-build/nginx-config/create-nginx-sites-conf.php $FRAMELIX_SYSTEMDIR/create-nginx-sites-conf.php
COPY docker-build/nginx-config/www $FRAMELIX_SYSTEMDIR/www
RUN rm /etc/nginx/sites-enabled/default

# install cronjobs
RUN crontab $FRAMELIX_SYSTEMDIR/cronjobs

# create some useful-scripts symlinks
RUN chmod +x $FRAMELIX_SYSTEMDIR/useful-scripts/* && ln -s $FRAMELIX_SYSTEMDIR/useful-scripts/* /usr/bin

# some additional build steps (to include appdata, etc...) for dev/production builds
RUN php -f $FRAMELIX_SYSTEMDIR/build-image.php "$FRAMELIX_BUILD_VERSION"

# let's go
RUN chmod +x "$FRAMELIX_SYSTEMDIR/entrypoint.sh"

# health check
HEALTHCHECK --interval=1m --timeout=3s CMD framelix_console all healthCheck -q || exit 1

SHELL ["/bin/bash", "-c"]
ENTRYPOINT $FRAMELIX_SYSTEMDIR/entrypoint.sh