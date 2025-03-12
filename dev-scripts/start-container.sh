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
  $DOCKER_CMD volume rm "${COMPOSE_PROJECT_NAME}_mariadb"
  $DOCKER_CMD volume rm "${COMPOSE_PROJECT_NAME}_postgres"
fi

$DOCKER_COMPOSE pull
$DOCKER_COMPOSE up -d
$DOCKER_COMPOSE_EXEC_APP "framelix_wait_for_ready"

