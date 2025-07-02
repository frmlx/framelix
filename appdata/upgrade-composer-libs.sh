#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
cd $SCRIPTDIR
rm composer.lock compeser.json
composer require --dev phpunit/phpunit phpstan/phpstan jetbrains/phpstorm-attributes