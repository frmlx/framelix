ARG OS_IMAGE=ubuntu:22.04
# CHECK OUT MORE AVAILABLE IMAGES FROM https://hub.docker.com/_/ubuntu
FROM $OS_IMAGE

ENV FRAMELIX_APPDATA="/framelix/appdata"
ENV FRAMELIX_DBDATA="/framelix/dbdata"
ENV FRAMELIX_USERDATA="/framelix/userdata"
ENV FRAMELIX_SYSTEMDIR="/framelix/system"
ENV FRAMELIX_UNIT_TESTS=0
# FORMAT: MODULENAME,SSL(1/0)[Default=1,OPTIONAL],PORTNR[Default=443/80,OPTIONAL],PRIVKEY_PATH[DEFAULT=SelfSigned,OPTIONAL],PUBKEY_PATH[DEFAULT=SelfSigned,OPTIONAL]; MODULENAME,...
ENV FRAMELIX_MODULES=""

RUN mkdir -p $FRAMELIX_APPDATA $FRAMELIX_DBDATA $FRAMELIX_USERDATA $FRAMELIX_SYSTEMDIR
VOLUME $FRAMELIX_DBDATA
VOLUME $FRAMELIX_USERDATA

# add better sources
RUN export DEBIAN_FRONTEND=noninteractive &&  \
    apt update &&  \
    apt install software-properties-common gnupg curl -y --no-install-recommends &&  \
    add-apt-repository ppa:ondrej/php -y &&  \
    add-apt-repository ppa:ondrej/nginx-mainline -y && \
    apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc' -y && \
    add-apt-repository 'deb https://mirror.netcologne.de/mariadb/repo/10.11/ubuntu jammy main' -y
ADD https://deb.nodesource.com/setup_19.x /root/nodesource_setup.sh
RUN bash /root/nodesource_setup.sh && rm /root/nodesource_setup.sh
# remove not needed libs after source updates
RUN export DEBIAN_FRONTEND=noninteractive &&  \
    apt -y autoremove software-properties-common gnupg

# install all required libs
RUN export DEBIAN_FRONTEND=noninteractive && \
    apt update && \
    apt install ca-certificates cron nginx mariadb-server nodejs php8.2-cli php8.2-fpm php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-sqlite3 php8.2-pgsql 7zip imagemagick git ghostscript nano -y --no-install-recommends && \
    apt upgrade -y

# system stuff
COPY docker-build/entrypoint.sh $FRAMELIX_SYSTEMDIR/entrypoint.sh
COPY docker-build/useful-scripts $FRAMELIX_SYSTEMDIR/useful-scripts
COPY docker-build/misc-conf/cronjobs $FRAMELIX_SYSTEMDIR/cronjobs

# imagemagick
COPY docker-build/misc-conf/imagemagick-policy.xml /etc/ImageMagick-6/policy.xml

# php
COPY docker-build/php-config/php.ini /etc/php/8.2/cli/php.ini
COPY docker-build/php-config/php.ini /etc/php/8.2/fpm/php.ini
COPY docker-build/php-config/php-xdebug.ini $FRAMELIX_SYSTEMDIR/php-xdebug.ini
COPY docker-build/php-config/fpm-pool.conf $FRAMELIX_SYSTEMDIR/fpm-pool.conf

# nginx
COPY docker-build/nginx-config/nginx.conf /etc/nginx/nginx.conf
COPY docker-build/nginx-config/nginx-ssl.crt $FRAMELIX_SYSTEMDIR/nginx-ssl.crt
COPY docker-build/nginx-config/nginx-ssl.key $FRAMELIX_SYSTEMDIR/nginx-ssl.key
COPY docker-build/nginx-config/snippets /etc/nginx/snippets/framelix
COPY docker-build/nginx-config/create-nginx-sites-conf.php $FRAMELIX_SYSTEMDIR/create-nginx-sites-conf.php
COPY docker-build/nginx-config/www $FRAMELIX_SYSTEMDIR/www
RUN rm /etc/nginx/sites-enabled/default

# mariadb
COPY docker-build/mariadb-conf/mariadb.cnf /etc/mysql/mariadb.conf.d/70-framelix.cnf

# install cronjobs
RUN crontab $FRAMELIX_SYSTEMDIR/cronjobs

# create some useful-scripts symlinks
RUN chmod +x $FRAMELIX_SYSTEMDIR/useful-scripts/* && ln -s $FRAMELIX_SYSTEMDIR/useful-scripts/* /usr/bin

# install composer
RUN framelix_composer_install

# let's go
RUN chmod +x "$FRAMELIX_SYSTEMDIR/entrypoint.sh"

# health check
HEALTHCHECK --interval=1m --timeout=3s CMD framelix_console healthCheck -q || exit 1

ENTRYPOINT $FRAMELIX_SYSTEMDIR/entrypoint.sh