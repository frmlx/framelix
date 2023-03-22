#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

cecho b "Run playwright tests"
echo "Available command line flags:"
echo "-c : Run with docker compose instead of docker"
echo ""

COMPOSE=0
while getopts "c" opt; do
  case $opt in
  c) COMPOSE=1 ;;
  esac
done

CMD="mkdir -p /framelix/userdata/playwright && chmod 0777 -R /framelix/userdata/playwright && rm -Rf /framelix/userdata/playwright/results && cd /framelix/appdata/playwright && echo \"<?php \Framelix\Framelix\Config::\\\$appSetupDone = true; \Framelix\Framelix\Config::\\\$salts['default'] = '0';\" > /framelix/userdata/Framelix/private/config/01-core.php && PLAYWRIGHT_BROWSERS_PATH=/framelix/userdata/playwright/cache npx playwright test"

if [ "$COMPOSE" == "1" ]; then
  docker compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c -- "$CMD"
else
  docker exec -t $DOCKER_CONTAINER_NAME bash -c -- "$CMD"
fi

RESULT=$?
if [ "$RESULT" == "0" ]; then
  echo -n "Passed" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
  echo -n "#00FF59" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
else
  echo -n "Error" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
  echo -n "#FF2100" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
fi

exit $RESULT