#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Start Docker Container for development"
echo "Available command line flags:"
echo "-c : Does run with docker compose instead of docker (Usefull for development with PhpStorm)"
echo "-d : Does delete and recreate the existing database volume (start with a new database)"
echo ""

while getopts "cd" opt; do
  case $opt in
  c) COMPOSE=1 ;;
  d) DEL_VOL=1 ;;
  esac
done

source $SCRIPTDIR/stop-container.sh

VOLUME_NAME="${COMPOSE_PROJECT_NAME}_vol"

if [ "$DEL_VOL" == "1" ]; then
  echo "Recreate volume $VOLUME_NAME"
  docker volume rm $VOLUME_NAME
fi
docker volume create $VOLUME_NAME

if [ "$COMPOSE" == "1" ]; then
  docker compose -f $SCRIPTDIR/docker-compose.yml up -d
  docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash "framelix_wait_for_ready"
else
  docker rm $COMPOSE_PROJECT_NAME

  docker run --name $COMPOSE_PROJECT_NAME -d \
    -p "${FRAMELIX_TEST_PORT}:${FRAMELIX_TEST_PORT}" \
    -p "${FRAMELIX_DOCS_PORT}:${FRAMELIX_DOCS_PORT}" \
    -v $ROOTDIR/appdata:/framelix/appdata \
    -v "${VOLUME_NAME}":/framelix/dbdata \
    -v $ROOTDIR/userdata:/framelix/userdata \
    -e FRAMELIX_MODULES=$FRAMELIX_MODULES \
    -e FRAMELIX_DEVMODE=$FRAMELIX_DEVMODE \
    nullixat/framelix:local

  docker exec -t $COMPOSE_PROJECT_NAME "framelix_wait_for_ready"

fi
