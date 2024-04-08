<?php

$version = $_SERVER['argv'][1];

function runCmd(string $cmd): void
{
    echo "=> CMD: $cmd\n";
    passthru($cmd, $status);
    if ($status) {
        exit($status);
    }
}

// a specific tag, include source into container (production builds)
if (preg_match("~^[0-9]+\.[0-9]+\.[0-9]+~i", $version)) {
    $tmpFolder = "/tmp/framelix-tag-$version";
    $modulesFolder = "/framelix/appdata/modules";
    echo "## Running build requirements for production build\n\n";
    echo "# Download framelix for current version $version from Github\n";
    runCmd("rm -rf $tmpFolder");
    runCmd("mkdir -p $tmpFolder");
    runCmd(
        "curl https://github.com/frmlx/framelix/archive/refs/tags/$version.zip -L --output $tmpFolder/package.zip"
    );
    echo "Done.\n\n";

    echo "# Extracting packages and move to appdata\n";
    runCmd("mkdir -p $modulesFolder");
    runCmd("7zz x $tmpFolder/package.zip -spf -y -o$tmpFolder");
    runCmd("mv $tmpFolder/*/appdata/modules/Framelix $modulesFolder/Framelix");
    runCmd("mv $tmpFolder/*/appdata/modules/FramelixDemo $modulesFolder/FramelixDemo");
    runCmd("mv $tmpFolder/*/appdata/modules/FramelixDocs $modulesFolder/FramelixDocs");
    runCmd("mv $tmpFolder/*/appdata/modules/FramelixStarter $modulesFolder/FramelixStarter");
    runCmd("rm -rf $tmpFolder");
    echo "Done.\n\n";

    // replace version number
    $coreFile = "/framelix/appdata/modules/Framelix/src/Framelix.php";
    $coreFileData = file_get_contents($coreFile);
    $coreFileData = str_replace(
        'public const string VERSION = "dev";',
        'public const string VERSION = "' . $version . '";',
        $coreFileData
    );
    file_put_contents($coreFile, $coreFileData);

    echo "# Running npm install and composer install for modules integrated into the image\n";
    runCmd("framelix_npm_modules_install");
    runCmd("framelix_composer_modules_install");
    echo "Done.\n\n";

    echo "Production build process completed.\n\n";
} elseif ($version !== 'dev' && $version !== 'master') {
    echo "Missing proper -v parameter, given: $version, but must be dev/master or tag name";
    exit(1);
}