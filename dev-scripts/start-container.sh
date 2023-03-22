#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Start/Build Docker Container for development"
echo "Available command line flags:"
echo "-c : Does build and run with docker compose instead of docker"
echo "-d : Does delete and recreate the existing database volume (start with a new database)"
echo "-b : Define the base repository/path for building the docker image"
echo "     local (default) = Building local ../Dockerfile"
echo "     main = Building from main framelix-docker repository"
echo "     release = Building latest release of docker hub at nullixat/framelix:latest"
echo "     gitworkflow = Building release of 'local' or 'release' depending on which version is newer"
echo "                   This speed up workflows as they usually use existing docker images from hub instead of rebuild"
echo ""

BUILD_BASE=local
while getopts "cdb:" opt; do
  case $opt in
  c) COMPOSE=1 ;;
  d) DEL_VOL=1 ;;
  b) BUILD_BASE=$OPTARG ;;
  esac
done

source $SCRIPTDIR/stop-container.sh

VOLUME_NAME="${DOCKER_CONTAINER_NAME}_vol"

BUILD_PATH=$ROOTDIR
COMPOSE_BUILD_KEY=context

if [ "$BUILD_BASE" == "gitworkflow" ]; then
  echo "Using newest version from local or docker hub"
  FOUND_VERSION=`wget -O - https://hub.docker.com/v2/namespaces/nullixat/repositories/framelix/tags 2>&1 | grep -c '"name":"'$VERSION'"'`
  BUILD_BASE=local
  if [ "$FOUND_VERSION" == "1" ]; then
    BUILD_BASE=latest
  fi
fi

if [ "$BUILD_BASE" == "local" ]; then
  echo "Using ../Dockerfile as build context"
fi

if [ "$BUILD_BASE" == "latest" ]; then
  echo "Using nullixat/framelix:latest from docker hub"
  BUILD_PATH="nullixat/framelix:latest"
  COMPOSE_BUILD_KEY=image
fi

if [ "$BUILD_BASE" == "main" ]; then
  echo "Using framelix-docker#main for build instead of dockerfile definition"
  BUILD_PATH="https://github.com/NullixAT/framelix-docker.git#main"
fi

if [ "$COMPOSE" == "1" ]; then
  docker compose -f $SCRIPTDIR/docker-compose.yml down
  if [ "$DEL_VOL" == "1" ]; then
    echo "Recreate volume $VOLUME_NAME"
    docker volume rm $VOLUME_NAME
  fi
  docker volume create $VOLUME_NAME
  cp $SCRIPTDIR/docker-compose.template.yml $SCRIPTDIR/docker-compose.yml

  if [ "$COMPOSE_BUILD_KEY" == "context" ]; then
    sed -i 's~build: ..~build:\n      '$COMPOSE_BUILD_KEY': '"$BUILD_PATH"'~g' $SCRIPTDIR/docker-compose.yml
  else
    sed -i 's~build: ..~'$COMPOSE_BUILD_KEY': '"$BUILD_PATH"'~g' $SCRIPTDIR/docker-compose.yml
  fi
  docker compose -f $SCRIPTDIR/docker-compose.yml up -d --build
  docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash "framelix_wait_for_ready"
else
  docker stop $DOCKER_CONTAINER_NAME
  docker rm $DOCKER_CONTAINER_NAME

  if [ "$DEL_VOL" == "1" ]; then
    echo "Recreate  volume $VOLUME_NAME"
    docker volume rm $VOLUME_NAME
  fi

  docker volume create $VOLUME_NAME

  BUILD_IMAGE_NAME=$DOCKER_CONTAINER_NAME
  if [ "$COMPOSE_BUILD_KEY" == "context" ]; then
    docker build -t $DOCKER_CONTAINER_NAME $BUILD_PATH
  else
    BUILD_IMAGE_NAME=$BUILD_PATH
  fi

  docker run --name $DOCKER_CONTAINER_NAME -d \
    -p $PUBLICPORT:443 \
    -v $ROOTDIR/appdata:/framelix/appdata_volume \
    -v "${DOCKER_CONTAINER_NAME}_vol":/framelix/dbdata \
    -v $ROOTDIR/userdata:/framelix/userdata \
    -e FRAMELIX_MODULE=$MODULENAME \
    -e FRAMELIX_UNIT_TESTS=$FRAMELIX_UNIT_TESTS \
    -e FRAMELIX_PLAYWRIGHT_TESTS=$FRAMELIX_PLAYWRIGHT_TESTS \
    $BUILD_IMAGE_NAME

  docker exec -t $DOCKER_CONTAINER_NAME "framelix_wait_for_ready"

fi
