#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

cecho b "Running tests"
echo "Available command line flags:"
echo "-t : Type of test: phpstan, phpunit, playwright, install-deps"
echo "-f : Specify the testfile."

TESTFILE=""
TESTTYPE=0
while getopts "f:t:" opt; do
  case $opt in
  f) TESTFILE=$OPTARG ;;
  t) TESTTYPE=$OPTARG ;;
  esac
done

DOCKER_EXECPARAMS=" compose -f $SCRIPTDIR/docker-compose.yml exec -t "
DOCKER_EXECPARAMS_APP="$DOCKER_EXECPARAMS app bash -c "
DOCKER_EXECPARAMS_MARIADB="$DOCKER_EXECPARAMS mariadb bash -c "

cecho y "[i] Running tests"

if [ $TESTTYPE == "install-deps" ]; then
  cecho b "# Install composer and npm dependencies"
  docker $DOCKER_EXECPARAMS_APP "cd /framelix/appdata && composer update && cd /framelix/appdata/playwright && npm install -y"
  exit $?
fi


if [ $TESTTYPE == "phpstan" ]; then
  cecho b "# Php Stan Static Code Analyzer"
  docker $DOCKER_EXECPARAMS_APP "cd /framelix/appdata  && framelix_php vendor/bin/phpstan analyze --memory-limit 1G --no-progress"
  exit $?
fi

cecho b "# Removing all userdata files and databases to start clean with each full test run"
docker $DOCKER_EXECPARAMS_APP "rm -Rfv /framelix/userdata/*"
echo ""
echo "Done."
echo ""

cecho b "# Drop databases"
docker $DOCKER_EXECPARAMS_MARIADB "mysql -u root -papp -e 'DROP DATABASE IF EXISTS unittests; DROP DATABASE IF EXISTS FramelixTests; DROP DATABASE IF EXISTS FramelixDocs; DROP DATABASE IF EXISTS FramelixStarter;'"
echo ""
echo "Done."
echo ""

cecho b "# Run appWarmup"
docker $DOCKER_EXECPARAMS_APP "framelix_console all appWarmup"
echo ""
echo "Done."
echo ""

if [ $TESTTYPE == "phpunit" ]; then
  # phpunit with process isolation have a bug with enabling xdebug on the fly with -d ini parameters
  # so, modifying the php config globally for the time this test is running
  cecho b "# Php Unit Tests"
  docker $DOCKER_EXECPARAMS_APP "cd /framelix/appdata && framelix_php_xdebug vendor/bin/phpunit --coverage-clover /framelix/userdata/clover.xml --log-junit /framelix/userdata/phpunit-results.xml --bootstrap modules/FramelixTests/tests/_bootstrap.php --configuration modules/FramelixTests/tests/_phpunit.xml && framelix_php hooks/after-phpunit.php"
  exit $?
fi

if [ $TESTTYPE == "playwright" ]; then
  cecho b "# Playwright End-to-End Tests"
  PLAYWRIGHT_CACHE=/framelix/system/playwright/cache

  if [ "$TESTFILE" != "" ]; then
    TESTFILE="/framelix/appdata/playwright/tests/$TESTFILE.spec.ts"
  fi

  docker $DOCKER_EXECPARAMS_APP "export PLAYWRIGHT_BROWSERS_PATH=$PLAYWRIGHT_CACHE  && mkdir -p /framelix/userdata/playwright/results && chmod 0777 -R /framelix/userdata/playwright && cd /framelix/appdata/playwright && npx playwright test $TESTFILE"

  if [ "$TESTFILE" == "" ]; then
    RESULT=$?
    if [ "$RESULT" == "0" ]; then
      echo -n "Passed" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
      echo -n "#00FF59" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
    else
      echo -n "Error" > $SCRIPTDIR/../userdata/playwright/badge-message.txt
      echo -n "#FF2100" > $SCRIPTDIR/../userdata/playwright/badge-color.txt
    fi
  fi

  exit $RESULT
fi