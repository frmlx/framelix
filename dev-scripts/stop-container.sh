#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

echo -n "Stopping $DOCKER_CONTAINER_NAME..."
docker compose -f $SCRIPTDIR/docker-compose.yml down &>/dev/null
docker stop $DOCKER_CONTAINER_NAME &>/dev/null
echo " Done"