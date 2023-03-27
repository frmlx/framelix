#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

cecho b "Running tests"
echo "Available command line flags:"
echo "-t : Type of test: phpstan, phpunit, playwright"
echo "-u : Update dependencies (composer and playwright deps)"

TESTTYPE=0
UPDATE=0
while getopts "ut:" opt; do
  case $opt in
  t) TESTTYPE=$OPTARG ;;
  u) UPDATE=1 ;;
  esac
done

DOCKERTYPE=0
DOCKER_EXECPARAMS="-f $SCRIPTDIR/docker-compose.yml exec -t app bash -c"
if [ ! -z "$(docker ps --filter 'name=framelix_tests-app' --filter 'status=running' --no-trunc -q)" ]; then
  DOCKERTYPE=compose
  DOCKER_EXECPARAMS=" compose -f $SCRIPTDIR/docker-compose.yml exec -t app bash -c "
else
  if [ ! -z "$(docker ps --filter 'name=framelix_tests' --filter 'status=running' --no-trunc -q)" ]; then
    DOCKERTYPE=docker
    DOCKER_EXECPARAMS=" exec -t $COMPOSE_PROJECT_NAME bash -c "
  fi
fi

if [ "$DOCKERTYPE" == "0" ]; then
  echo "No $COMPOSE_PROJECT_NAME container is running"
  exit 1
fi

echo "Running tests on docker container from type '$DOCKERTYPE'"


if [ $TESTTYPE == "phpstan" ]; then
  docker $DOCKER_EXECPARAMS "cd /framelix/appdata && composer update && framelix_php vendor/bin/phpstan analyze --memory-limit 1G --no-progress"
  exit $?
fi

if [ $TESTTYPE == "phpunit" ]; then
  docker $DOCKER_EXECPARAMS "mysql -u root -papp -e 'DROP DATABASE IF EXISTS unittests; DROP DATABASE IF EXISTS FramelixTests;'"
  docker $DOCKER_EXECPARAMS "framelix_console '*' appWarmup"
  docker $DOCKER_EXECPARAMS "cd /framelix/appdata && composer update && framelix_php vendor/bin/phpunit --coverage-clover /framelix/userdata/tmp/clover.xml --bootstrap modules/FramelixTests/tests/_bootstrap.php --configuration  modules/FramelixTests/tests/_phpunit.xml && framelix_php hooks/after-phpunit.php"
  exit $?
fi

if [ $TESTTYPE == "playwright" ]; then
  $PLAYWRIGHT_CACHE=/framelix/system/playwright/cache
  docker $DOCKER_EXECPARAMS "mysql -u root -papp -e 'DROP DATABASE IF EXISTS FramelixTests;'"
  docker $DOCKER_EXECPARAMS "framelix_console '*' appWarmup"
  docker $DOCKER_EXECPARAMS "export PLAYWRIGHT_BROWSERS_PATH=$PLAYWRIGHT_CACHE && rm -f /framelix/userdata/*/private/config/01-core.php && rm -f /framelix/userdata/*/private/config/02-ui.php && mkdir -p /framelix/userdata/playwright && chmod 0777 -R /framelix/userdata/playwright && rm -Rf /framelix/userdata/playwright/results && cd /framelix/appdata/playwright && npm install -y && npx playwright install-deps && npx playwright install chromium && npx playwright test"

  RESULT=$?
  if [ "$RESULT" == "0" ]; then
    echo -n "Passed" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
    echo -n "#00FF59" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
  else
    echo -n "Error" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
    echo -n "#FF2100" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
  fi

  exit $RESULT
fi

cecho b "Framelix Testrunner"
echo "Available command line flags:"
echo "-t : Testtypes available: phpstan, phpunit, playwright"
echo ""