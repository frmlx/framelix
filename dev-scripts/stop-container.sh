#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

echo -n "Stopping $COMPOSE_PROJECT_NAME..."
docker compose -f $SCRIPTDIR/docker-compose.yml down &>/dev/null
docker stop $COMPOSE_PROJECT_NAME &>/dev/null
echo " Done"