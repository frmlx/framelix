#!/usr/bin/bash

SCRIPTDIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source $SCRIPTDIR/lib.sh

# fix corrupt db in case tests have destroyed the db
docker exec -t $DOCKER_CONTAINER_NAME bash -c "mysql -u root -papp -e 'create database if not exists app'"

# run php unit
docker exec -t $DOCKER_CONTAINER_NAME bash -c "cd /framelix/appdata && framelix_php vendor/phpunit.phar --coverage-clover /framelix/userdata/tmp/clover.xml --bootstrap modules/FramelixTests/tests/_bootstrap.php --configuration  modules/FramelixTests/tests/_phpunit.xml && framelix_php hooks/after-phpunit.php"

exit $?