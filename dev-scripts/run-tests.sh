#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

cecho b "Running tests"
echo "Available command line flags:"
echo "-t : Type of test: phpstan, phpunit, playwright"

TESTTYPE=0
while getopts "t:" opt; do
  case $opt in
  t) TESTTYPE=$OPTARG ;;
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

cecho y "[i] Running tests on docker container from type '$DOCKERTYPE'"

if [ $TESTTYPE == "phpstan" ]; then
  cecho b "# Php Stan Static Code Analyzer"
  docker $DOCKER_EXECPARAMS "cd /framelix/appdata && composer update && framelix_php vendor/bin/phpstan analyze --memory-limit 1G --no-progress"
  exit $?
fi

cecho b "# Removing all userdata files and databases to start clean with each full test run"
docker $DOCKER_EXECPARAMS "rm -Rfv /framelix/userdata/*"
echo ""
echo "Done."
echo ""

cecho b "# Drop databases"
docker $DOCKER_EXECPARAMS "mysql -u root -papp -e 'DROP DATABASE IF EXISTS unittests; DROP DATABASE IF EXISTS FramelixTests; DROP DATABASE IF EXISTS FramelixDocs; DROP DATABASE IF EXISTS FramelixStarter;'"
echo ""
echo "Done."
echo ""

cecho b "# Run appWarmup"
docker $DOCKER_EXECPARAMS "framelix_console '*' appWarmup"
echo ""
echo "Done."
echo ""

if [ $TESTTYPE == "phpunit" ]; then
  # phpunit with process isolation have a bug with enabling xdebug on the fly with -d ini parameters
  # so, modifying the php config globally for the time this test is running
  INI_PATH=/etc/php/8.2/cli/conf.d/99-phpunit.ini
  cecho b "# Php Unit Tests"
  docker $DOCKER_EXECPARAMS "printf 'zend_extension=xdebug.so\nxdebug.mode=coverage\nmemory_limit=-1' > $INI_PATH"
  docker $DOCKER_EXECPARAMS "cd /framelix/appdata && composer update && framelix_php vendor/bin/phpunit --coverage-clover /framelix/userdata/tmp/clover.xml --bootstrap modules/FramelixTests/tests/_bootstrap.php --configuration  modules/FramelixTests/tests/_phpunit.xml -d zend_extension=xdebug.so -d xdebug.mode=coverage -d memory_limit=-1 && framelix_php hooks/after-phpunit.php"
  docker $DOCKER_EXECPARAMS "rm -f $INI_PATH"
  exit $?
fi

if [ $TESTTYPE == "playwright" ]; then
  cecho b "# Playwright End-to-End Tests"
  PLAYWRIGHT_CACHE=/framelix/system/playwright/cache
  docker $DOCKER_EXECPARAMS "export PLAYWRIGHT_BROWSERS_PATH=$PLAYWRIGHT_CACHE && mkdir -p /framelix/userdata/playwright/results && chmod 0777 -R /framelix/userdata/playwright && cd /framelix/appdata/playwright && npm install -y && npx playwright install-deps && npx playwright install chromium && npx playwright test"

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