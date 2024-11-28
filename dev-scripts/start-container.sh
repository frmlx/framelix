#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Start Docker Container for development"
echo "Available command line flags:"
echo "-d : Does delete and recreate the existing database volumes"
echo ""

while getopts "cd" opt; do
  case $opt in
  d) DEL_VOL=1 ;;
  esac
done

source $SCRIPTDIR/stop-container.sh


if [ "$DEL_VOL" == "1" ]; then
  echo "Delete database volumes"
  docker volume rm "${COMPOSE_PROJECT_NAME}_mariadb"
  docker volume rm "${COMPOSE_PROJECT_NAME}_postgres"
fi

docker compose $COMPOSER_FILE_ARGS pull --ignore-pull-failures
docker compose $COMPOSER_FILE_ARGS up -d
docker compose $COMPOSER_FILE_ARGS exec -t app bash -c "framelix_wait_for_ready"

