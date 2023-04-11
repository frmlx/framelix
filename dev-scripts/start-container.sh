#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Start Docker Container for development"
echo "Available command line flags:"
echo "-d : Does delete and recreate the existing database volume (start with a new database container)"
echo ""

while getopts "cd" opt; do
  case $opt in
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
docker compose -f $SCRIPTDIR/docker-compose.yml up -d
docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c "framelix_wait_for_ready"

