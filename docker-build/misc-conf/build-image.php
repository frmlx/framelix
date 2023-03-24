<?php

$buildType = $_SERVER['argv'][1];
$version = file_get_contents("/framelix/system/VERSION");

if ($buildType === 'docker-hub') {
    $tmpFolder = "/tmp/framelix-tag-$version";
    echo "## Running build requirements for Docker HUB build";
    echo "# Clone framelix repository for current version";
    passthru("git clone --depth 1 --branch $version https://github.com/NullixAT/framelix.git $tmpFolder", $status);
    if ($status) {
        exit($status);
    }

    echo "# Running npm install and composer install for docker build";
    passthru("framelix_npm_modules_install", $status);
    if ($status) {
        exit($status);
    }
    passthru("framelix_composer_modules_install", $status);
    if ($status) {
        exit($status);
    }
}

exit(1);