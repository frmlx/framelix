#!/usr/bin/bash

cecho() {
  local code="\033["
  case "$1" in
  black | bk) color="${code}0;30m" ;;
  red | r) color="${code}1;31m" ;;
  green | g) color="${code}1;92m" ;;
  yellow | y) color="${code}1;93m" ;;
  blue | b) color="${code}1;34m" ;;
  purple | p) color="${code}1;35m" ;;
  cyan | c) color="${code}1;36m" ;;
  gray | gr) color="${code}0;37m" ;;
  *) local text="$1" ;;
  esac
  [ -z "$text" ] && local text="$color$2${code}0m"
  echo -e "$text"
}

# in case the ready file already exist, delete it
rm -f /framelix/system/READY

cecho b "# FRAMELIX DOCKER - 😜  Huhuu!"
echo ""

if [ -z "$FRAMELIX_MODULES" ]; then
  cecho r "Env FRAMELIX_MODULES not set. Aborting."
  exit 1
fi

if [ ! -d "$FRAMELIX_APPDATA/modules" ]; then
  cecho r "Missing Framelix core module $FRAMELIX_APPDATA/modules/Framelix. Aborting."
  exit 1
fi

cecho y "# Checking required folder mappings and variables"
echo ""

CHECKFOLDER=/framelix/userdata
if [ ! -w "$CHECKFOLDER" ]; then
  echo "Missing $CHECKFOLDER folder mapping(or the folder isn't writable)."
  echo "This folder must point to folder on your host (volume isn't recommended)."
  echo "This folder will contain files created by users in your app."
  echo "For a fresh installation you must use a new empty folder. Aborting."
  exit 1
fi
echo "$CHECKFOLDER OK."
echo "Done."
echo ""

cecho y "# Checking required users and groups for existence"
echo ""
NGINX_USER=$(stat -L -c %u $FRAMELIX_USERDATA)
NGINX_GROUP=$(stat -L -c %g $FRAMELIX_USERDATA)
NGINX_USERNAME=$(id -n -u ${NGINX_USER} 2>/dev/null)
NGINX_GROUPNAME=$(getent group ${NGINX_GROUP} | cut -d: -f1)

if [ -z "$NGINX_GROUPNAME" ]; then
  NGINX_GROUPNAME="framelix_$NGINX_GROUP"
  groupadd -g "$NGINX_GROUP" "$NGINX_GROUPNAME"
  echo "Created group '$NGINX_GROUPNAME' with ID $NGINX_GROUP because it didn't exist"
fi

if [ -z "$NGINX_USERNAME" ]; then
  NGINX_USERNAME="framelix_$NGINX_USER"
  useradd -g "$NGINX_GROUP" -s /usr/bin/bash --no-create-home "$NGINX_USERNAME"
  echo "Created user '$NGINX_USERNAME' with ID $NGINX_USER because it didn't exist"
fi

mkdir -p /home/$NGINX_USERNAME
chown $NGINX_USERNAME:$NGINX_GROUPNAME /home/$NGINX_USERNAME

echo "Nginx/PHP starting with UID($NGINX_USER)/GID($NGINX_GROUP) based on $FRAMELIX_USERDATA permission"
echo "Done."
echo ""

# create nginx config files based on env variables
cecho y "# Creating nginx config files based on environment variables"
echo ""

echo "user $NGINX_USERNAME $NGINX_GROUPNAME;" >/etc/nginx/nginx-framelix-dynamic.conf

php -f /framelix/system/create-nginx-sites-conf.php

if [ "$?" != "0" ]; then
  exit 1
fi

echo "Done."
echo ""

cecho y "# Starting php fpm service"
echo ""

# create config based on env variables
cat /framelix/system/fpm-pool.conf >/etc/php/8.3/fpm/pool.d/www.conf
echo "
user = $NGINX_USERNAME
group = $NGINX_GROUPNAME
listen.owner = $NGINX_USERNAME
listen.group = $NGINX_GROUPNAME" >>/etc/php/8.3/fpm/pool.d/www.conf
php-fpm8.3 -R -y /etc/php/8.3/fpm/php-fpm.conf
if [ "$?" != "0" ]; then
  cecho r "Error starting php-fpm8.3 Aborting."
  exit 1
fi

echo "Done."
echo ""

cecho y "# Starting nginx webserver"
echo ""

# truncate/create error log file
echo "" >/var/log/nginx-error.log

# start server
service nginx start

echo ""
echo "Done."
echo ""

cecho y "# Starting cronjobs"
echo ""
service cron start
echo ""
echo "Done."
echo ""

cecho y "# Cleanup before warmup"
echo ""
rm /framelix/userdata/tmp/newest-version.json >/dev/null 2>&1
echo "" > /var/log/framelix-cron.log
echo ""
echo "Done."
echo ""

cecho y "# Do app warmup"
echo ""
framelix_console all appWarmup
echo ""

cecho y "# Set correct files owners for folder that need to be writable"
chown -L "$NGINX_USERNAME":"$NGINX_GROUPNAME" $FRAMELIX_USERDATA
chown -L -R "$NGINX_USERNAME":"$NGINX_GROUPNAME" $FRAMELIX_APPDATA/modules/*/public/dist $FRAMELIX_APPDATA/modules/*/_meta
echo ""
echo "Done."
echo ""

cecho y "# Start NodeJS module servers"
echo ""
framelix_start_module_servers
echo ""
echo "Done."
echo ""

cecho y "# Server software versions now used"
echo ""
output=`nginx -v 2>&1`
echo "* Nginx: $output"
output=`php -r 'echo PHP_VERSION;'`
echo "* PHP: $output"
output=`node -v`
echo "* NodeJS: $output"
echo ""
echo ""

echo "" >/framelix/system/READY
cecho g "# ✅  FRAMELIX DOCKER INITIALIZED - Tailing all logs from here on"
echo ""
echo ""
echo ""

cecho y "# Processlist after startup"
echo ""
ps -AF
echo ""
echo ""

cecho y "# All /var/log/*.log files"
echo ""
tail -f /var/log/*.log -f /var/log/nginx/*.log
