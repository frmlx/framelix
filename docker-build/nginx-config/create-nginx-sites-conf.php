<?php
// a script to build the required nginx-sites.conf depending on the FRAMELIX_PORTMAP environment variable

$templateNonSSL = '
server {
    listen 0.0.0.0:{port};
    root /framelix/appdata/modules/{module}/public;
    include /etc/nginx/snippets/framelix/vhost.conf;
}
';

$templateSSL = '
server {
    listen 0.0.0.0:{port} ssl;
    root /framelix/appdata/modules/{module}/public;

    ssl_certificate     /framelix/system/nginx-ssl.crt;
    ssl_certificate_key /framelix/system/nginx-ssl.key;

    include /etc/nginx/snippets/framelix/vhost.conf;
}
';

$nginxSitesPath = "/framelix/system/nginx-sites.conf";


$modules = explode(";", $_SERVER['FRAMELIX_MODULES'] ?? '1');
$validModules = 0;
$allValid = true;

$contents = [];
foreach ($modules as $row) {
    $parts = explode(",", $row);
    $moduleName = $parts[0];
    if (!$moduleName) {
        continue;
    }
    $modulePath = "/framelix/appdata/modules/" . $moduleName;
    if (!file_exists($moduleName)) {
        echo "Framelix module '$moduleName' not exist. Aborting.\n";
        $allValid = false;
        continue;
    }
    $ssl = ($parts[1] ?? null) === '1';
    $port = ($parts[2] ?? null) ? (int)$parts[2] : ($ssl ? 443 : 80);
    $privKeyFile = ($parts[3] ?? null);
    $pubKeyFile = ($parts[4] ?? null);
    if ($privKeyFile && !file_exists($privKeyFile)) {
        echo "Framelix module '$moduleName' -> PrivKey Path '$privKeyFile'not exist. Aborting.\n";
        $allValid = false;
        continue;
    }
    if ($pubKeyFile && !file_exists($pubKeyFile)) {
        echo "Framelix module '$moduleName' -> PubKey Path '$pubKeyFile'not exist. Aborting.\n";
        $allValid = false;
        continue;
    }
    $validModules++;
}

if (!$validModules) {
    echo "No valid Framelix modules given in FRAMELIX_MODULES environment variable. Aborting.";
}

exit($allValid && $validModules > 0 ? 0 : 1);