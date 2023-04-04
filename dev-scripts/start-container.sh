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
  DOCKER_EXECPARAMS="compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c"
else
  DOCKER_EXECPARAMS="exec -t $COMPOSE_PROJECT_NAME bash -c"
  docker rm $COMPOSE_PROJECT_NAME

  docker run --name $COMPOSE_PROJECT_NAME -d \
    -p "${FRAMELIX_TEST_PORT}:${FRAMELIX_TEST_PORT}" \
    -p "${FRAMELIX_DOCS_PORT}:${FRAMELIX_DOCS_PORT}" \
    -p "${FRAMELIX_STARTER_PORT}:${FRAMELIX_STARTER_PORT}" \
    -v $ROOTDIR/appdata:/framelix/appdata \
    -v "${VOLUME_NAME}":/framelix/dbdata \
    -v $ROOTDIR/userdata:/framelix/userdata \
    --env-file $SCRIPTDIR/.env  \
    $USE_IMAGE_NAME
fi
docker $DOCKER_EXECPARAMS "framelix_wait_for_ready"

