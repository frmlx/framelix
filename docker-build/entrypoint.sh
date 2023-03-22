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

start_mysql() {
  mkdir /run/mysqld/
  chown mysql:mysql /run/mysqld/
  /usr/bin/mysqld_safe --basedir=/usr --datadir=$FRAMELIX_DBDATA --plugin-dir=/usr/lib/mysql/plugin --user=mysql --pid-file=/run/mysqld/mysqld.pid --socket=/run/mysqld/mysqld.sock --skip-syslog --log-error=/var/log/mariadb-error.log &
  sleep 1
  echo -n "Wait for DB to come up ..."
  while [ 1 ]; do
    mysql -u root -papp -e "quit" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
      echo " OK"
      break
    fi
    echo -n .
    sleep 1
  done
  cecho b "# Run mysql_upgrade"
  echo ""
  # mysql upgrade in case database has been upgraded
  mysql_upgrade -u root -papp
  echo ""
  echo "Done"
  echo "Mysql Server started"
}

cecho b "# FRAMELIX DOCKER - 😜  Huhuu!"
echo ""

if [ -z "$FRAMELIX_MODULE" ]; then
  cecho r "Env FRAMELIX_MODULE not set. Aborting."
  exit 1
fi

if [ -d "$FRAMELIX_APPDATA/modules" ]; then
  cecho y "# Using already integrated appdata folder $FRAMELIX_APPDATA."
  echo ""
else
  cecho y "# Using dynamic appdata mapped volume"
  ln -f -s $FRAMELIX_APPDATA_VOLUME $FRAMELIX_APPDATA
  echo ""

  cecho y "# Run npm install"
  echo ""
  framelix_npm_modules_install
  echo ""
  echo "Done."
  echo ""

  cecho y "# Run composer install"
  echo ""
  framelix_composer_modules_install
  echo ""
  echo "Done."
  echo ""

fi

cecho y "# Checking required folder mappings and variables"
echo ""

CHECKFOLDER=$FRAMELIX_APPDATA
if [ ! -w "$CHECKFOLDER" ]; then
  cecho r "Missing $CHECKFOLDER folder (or the folder isn't writable)."
  echo "This folder must contain your app files. Aborting."
  exit 1
fi
echo "$CHECKFOLDER OK."

CHECKFOLDER=$FRAMELIX_DBDATA
if [ ! -w "$CHECKFOLDER" ]; then
  cecho r "Missing $CHECKFOLDER folder mapping (or the folder isn't writable)."
  echo "This folder must point to a mariadb data folder."
  echo "For a fresh installation you must use a new empty folder. Aborting."
  exit 1
fi
echo "$CHECKFOLDER OK."

CHECKFOLDER=/framelix/userdata
if [ ! -w "$CHECKFOLDER" ]; then
  echo "Missing $CHECKFOLDER folder mapping(or the folder isn't writable)."
  echo "This folder will contain files created by users in your app."
  echo "For a fresh installation you must use a new empty folder. Aborting."
  exit 1
fi
echo "$CHECKFOLDER OK."
echo "Done."
echo ""

cecho y "# Write current module name \"$FRAMELIX_MODULE\" to /framelix/system/MODULE"
echo ""
echo $FRAMELIX_MODULE >/framelix/system/MODULE
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

echo "Nginx/PHP run with UID($NGINX_USER)/GID($NGINX_GROUP) based on $FRAMELIX_USERDATA permission"
echo "Done."
echo ""

cecho y "# Starting MariaDB service"
echo ""

echo "[mysqld]
innodb_buffer_pool_size=128M" >/etc/mysql/mariadb.conf.d/71-framelix.cnf
# truncate/create error log file

echo "" >/var/log/mariadb-error.log
echo "" >/var/log/mariadb-slow.log
chmod 0777 /var/log/mariadb-*

# setup db
if [ ! -d "$FRAMELIX_DBDATA/mysql" ]; then
  echo "Fresh database directory - Installing database"
  mysql_install_db \
    --user=mysql \
    --basedir=/usr \
    --datadir=$FRAMELIX_DBDATA \
    --skip-test-db \
    --default-time-zone=SYSTEM \
    --enforce-storage-engine= \
    --skip-log-bin \
    --expire-logs-days=0 \
    --loose-innodb_buffer_pool_load_at_startup=0 \
    --loose-innodb_buffer_pool_dump_at_shutdown=0
  start_mysql
  # update root password to a default
  mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'app';"
else
  start_mysql
  # upgrade database if required
  mysql_upgrade -u root -papp
fi

mysql -uroot -papp -e "CREATE DATABASE IF NOT EXISTS app;"
echo ""

cecho y "# Checking appdata"
echo ""
if [ ! -d "$FRAMELIX_APPDATA/modules" ]; then
  cecho r "$FRAMELIX_APPDATA not containing valid app files, aborting."
  exit 1
fi
if [ ! -d "$FRAMELIX_APPDATA/modules/$FRAMELIX_MODULE/public" ]; then
  cecho r "Appmodule $FRAMELIX_MODULE not installed properly. Aborting."
  exit 1
fi
echo "Done."
echo ""

# install extras for unit tests
if [ $FRAMELIX_UNIT_TESTS -eq 1 ]; then
  cecho y "# Unit Tests active - Installing tests dependencies"
  export DEBIAN_FRONTEND=noninteractive
  apt update
  apt install php8.2-xdebug -y
  cp $FRAMELIX_SYSTEMDIR/php-xdebug.ini /etc/php/8.2/cli/conf.d/21-xdebug.ini
  rm /etc/php/8.2/fpm/conf.d/20-xdebug.ini
  echo "Done."
  echo ""

  cecho y "# Create required test folders and files"
  echo ""
  mkdir -p /opt/phpstorm-coverage
  chmod 0777 /opt/phpstorm-coverage
  echo "Done."
  echo ""

  cecho y "# Recreate unittests database"
  mysql -u root -papp -e "DROP DATABASE IF EXISTS unittests; CREATE DATABASE  unittests;"
  echo ""
  echo "Done."
  echo ""

  cecho y "# Unit Tests specials done"
  echo ""
fi

# install extras for playwright tests
if [ "$FRAMELIX_PLAYWRIGHT_TESTS" == "1" ]; then
  cecho y "# Playwright Tests active - Installing playwright and deps"
  echo ""
  export DEBIAN_FRONTEND=noninteractive
  mkdir -p $FRAMELIX_USERDATA/playwright
  chmod 0777 -R $FRAMELIX_USERDATA/playwright
  cd $FRAMELIX_APPDATA/playwright
  npm cache clean -force
  npm install -y
  # fetching command and removing su root from that command as it will break this installation
  CMD=$( npx --yes playwright install-deps chromium --dry-run )
  eval $CMD
  PLAYWRIGHT_BROWSERS_PATH=$FRAMELIX_USERDATA/playwright/cache npx --yes playwright install chromium
  echo ""
  cecho y "# Playwright setup done"
  echo ""
fi

cecho y "# Starting php fpm service"
echo ""

# create config based on env variables
cat /framelix/system/fpm-pool.conf >/etc/php/8.2/fpm/pool.d/www.conf
echo "
user = $NGINX_USERNAME
group = $NGINX_GROUPNAME
listen.owner = $NGINX_USERNAME
listen.group = $NGINX_GROUPNAME" >>/etc/php/8.2/fpm/pool.d/www.conf
php-fpm8.2 -R -y /etc/php/8.2/fpm/php-fpm.conf
echo "Done."
echo ""

cecho y "# Starting nginx webserver"
echo ""

# create config files based on env variables
echo "user $NGINX_USERNAME $NGINX_GROUPNAME;" >/etc/nginx/nginx-framelix-dynamic.conf

cp /framelix/system/nginx-sites.conf /etc/nginx/sites-enabled/framelix-sites.conf
sed -i "s/{module}/$FRAMELIX_MODULE/g" /etc/nginx/sites-enabled/framelix-sites.conf

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
echo ""
echo "Done."
echo ""

cecho y "# Do app warmup"
echo ""
framelix_console appWarmup
echo ""

cecho y "# Set correct files owners for folder that need to be writable"
mkdir -p $FRAMELIX_USERDATA/tmp
chown -L "$NGINX_USERNAME":"$NGINX_GROUPNAME" $FRAMELIX_USERDATA $FRAMELIX_USERDATA/tmp
chown -L -R "$NGINX_USERNAME":"$NGINX_GROUPNAME" $FRAMELIX_APPDATA/modules/*/public/dist $FRAMELIX_APPDATA/modules/*/tmp
echo ""
echo "Done."
echo ""

cecho y "# Server software versions now used"
echo ""
output=`nginx -v 2>&1`
echo "Nginx: $output"
output=`mysql --version 2>&1`
echo "MariaDB: $output"
output=`php -r 'echo PHP_VERSION;'`
echo "PHP: $output"
output=`node -v`
echo "NodeJS: $output"
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
