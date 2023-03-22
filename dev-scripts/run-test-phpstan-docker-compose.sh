#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c "cd /framelix/appdata && framelix_php vendor/phpstan.phar analyze"
exit $?