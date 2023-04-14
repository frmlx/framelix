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
  docker volume rm "${VOLUME_NAME}_mariadb"
  docker volume rm "${VOLUME_NAME}_postgres"
fi

docker compose -f $SCRIPTDIR/docker-compose.yml up -d
docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c "framelix_wait_for_ready"

