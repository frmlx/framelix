#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build Docker Image"
echo "Available command line flags:"
echo "-t : Type of build: dev, prod"

BUILD_TYPE=0
while getopts "t:" opt; do
  case $opt in
  t) BUILD_TYPE=$OPTARG ;;
  esac
done


if [ "$BUILD_TYPE" != "dev" ] && [ "$BUILD_TYPE" != "prod" ]; then
  echo "Please specify build type 'dev' or 'prod'"
  exit 1
fi

source $SCRIPTDIR/stop-container.sh
docker image rm $DOCKER_TAGNAME_LOCAL

TMPFOLDER=$SCRIPTDIR/../tmp/appdata_dev
rm -Rf $TMPFOLDER

if [ "$BUILD_TYPE" == "dev" ]; then
  cecho b "# Copy required appdata dev which the build process integrates into the container"
  SRCFOLDER=$SCRIPTDIR/../appdata
  mkdir -p $TMPFOLDER/modules
  cp  $SRCFOLDER/* $TMPFOLDER > /dev/null 2>&1
  cp -R $SRCFOLDER/playwright $TMPFOLDER/playwright
  cp -R $SRCFOLDER/modules/Framelix $TMPFOLDER/modules/Framelix
  cp -R $SRCFOLDER/modules/FramelixDocs $TMPFOLDER/modules/FramelixDocs
  cp -R $SRCFOLDER/modules/FramelixStarter $TMPFOLDER/modules/FramelixStarter
fi

docker build -t $COMPOSE_PROJECT_NAME --build-arg "FRAMELIX_BUILD_TYPE=$BUILD_TYPE" $SCRIPTDIR/..

if [ "$?" != "0" ]; then
  cecho r "Build failed"
  exit 1
fi

docker tag $COMPOSE_PROJECT_NAME $DOCKER_TAGNAME_LOCAL

if [ "$BUILD_TYPE" == "dev" ]; then
  cecho y "# Installing dev dependencies in the container"
  source $SCRIPTDIR/start-container.sh

  # phpunit
  docker exec -t $COMPOSE_PROJECT_NAME bash -c "export DEBIAN_FRONTEND=noninteractive && apt install php8.2-xdebug -y && cp /framelix/system/php-xdebug.ini /etc/php/8.2/cli/conf.d/21-xdebug.ini && rm /etc/php/8.2/fpm/conf.d/20-xdebug.ini && mkdir -p /opt/phpstorm-coverage && chmod 0777 /opt/phpstorm-coverage"

  # phpstan
  docker exec -t $COMPOSE_PROJECT_NAME bash -c "cd /framelix/appdata && composer update"

  # playwright
  docker exec -t $COMPOSE_PROJECT_NAME bash -c "cd /framelix/appdata/playwright && npm install -y && npx playwright install-deps && npx playwright install chromium"

  # commiting changes to container (instead of tagging)
  docker commit $COMPOSE_PROJECT_NAME $DOCKER_TAGNAME_LOCAL
  source $SCRIPTDIR/stop-container.sh
  echo ""
  echo "Done."
  echo ""
elif [ "$BUILD_TYPE" == "dev" ]; then
  docker tag $COMPOSE_PROJECT_NAME $DOCKER_TAGNAME_LOCAL
fi

