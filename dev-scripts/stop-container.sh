#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

echo -n "Stopping $COMPOSE_PROJECT_NAME..."
$DOCKER_COMPOSE down &>/dev/null
echo " Done"