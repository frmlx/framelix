<?php

$buildType = $_SERVER['argv'][1];
$version = file_get_contents("/framelix/system/VERSION");

function runCmd(string $cmd): void
{
    echo "=> CMD: $cmd\n";
    passthru($cmd, $status);
    if ($status) {
        exit($status);
    }
}

if ($buildType === 'dev') {
    echo "## Running build requirements for DEV build\n\n";

    echo "## Installing composer deps\n\n";
    runCmd("cd /framelix/appdata && composer update");
    echo "Done.\n\n";

    echo "## Installing PhpUnit deps\n\n";
    runCmd("mkdir -p /opt/phpstorm-coverage && chmod 0777 /opt/phpstorm-coverage");
    echo "Done.\n\n";

    $cacheFolder = "/framelix/system/playwright/cache";
    echo "## Installing Playwright deps\n\n";
    runCmd("mkdir -p $cacheFolder && chmod 0777 $cacheFolder && export PLAYWRIGHT_BROWSERS_PATH=$cacheFolder && cd /framelix/appdata/playwright && npm install -y && npx playwright install-deps && npx playwright install chromium");
    echo "Done.\n\n";

    echo "# Running npm install and composer install for modules\n";
    runCmd("framelix_npm_modules_install");
    runCmd("framelix_composer_modules_install");
    echo "Done.\n\n";

    echo "Dev build process completed.\n\n";
}

if ($buildType === 'prod') {
    $tmpFolder = "/tmp/framelix-tag-$version";
    $modulesFolder = "/framelix/appdata/modules";
    echo "## Running build requirements for Docker HUB build\n\n";
    echo "# Download framelix for current version $version from Github\n";
    runCmd("rm -rf $tmpFolder");
    runCmd("mkdir -p $tmpFolder");
    runCmd(
        "curl https://github.com/NullixAT/framelix/archive/refs/tags/$version.zip -L --output $tmpFolder/package.zip"
    );
    echo "Done.\n\n";
    echo "# Extracting packages and move to appdata\n";
    runCmd("mkdir -p $modulesFolder");
    runCmd("7zz x $tmpFolder/package.zip -spf -y -o$tmpFolder");
    runCmd("mv $tmpFolder/*/appdata/modules/Framelix $modulesFolder/Framelix");
    runCmd("mv $tmpFolder/*/appdata/modules/FramelixDocs $modulesFolder/FramelixDocs");
    runCmd("mv $tmpFolder/*/appdata/modules/FramelixStarter $modulesFolder/FramelixStarter");
    runCmd("rm -rf $tmpFolder");
    echo "Done.\n\n";

    echo "# Running npm install and composer install for modules\n";
    runCmd("framelix_npm_modules_install");
    runCmd("framelix_composer_modules_install");
    echo "Done.\n\n";

    echo "Docker hub build process completed.\n\n";
}