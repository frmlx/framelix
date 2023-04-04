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

    ssl_certificate     {pubKeyPath};
    ssl_certificate_key {privKeyPath};

    include /etc/nginx/snippets/framelix/vhost.conf;
}
';

$nginxSitesPath = "/etc/nginx/sites-enabled/framelix-sites.conf";
$envConfigPath = "/framelix/system/environment.json";

$modules = explode(";", trim($_SERVER['FRAMELIX_MODULES'] ?? '1', " \"'"));
$validModules = 0;
$allValid = true;

$contents = [];
$envConfig = [];
foreach ($modules as $row) {
    $parts = explode(",", $row);
    $moduleName = $parts[0];
    if (!$moduleName) {
        continue;
    }
    $modulePath = "/framelix/appdata/modules/" . $moduleName . "/public/index.php";
    if (!file_exists($modulePath)) {
        echo "Framelix module '$moduleName' entry point not exist at '$modulePath'. Aborting.\n";
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
    $content = trim($ssl ? $templateSSL : $templateNonSSL);
    $content = str_replace(['{port}', '{module}'], [$port, $moduleName], $content);
    if ($ssl) {
        $content = str_replace('{privKeyPath}', $privKeyFile ?: '/framelix/system/nginx-ssl.key', $content);
        $content = str_replace('{pubKeyPath}', $pubKeyFile ?: '/framelix/system/nginx-ssl.crt', $content);
    }
    $contents[] = $content;
    $envConfig['moduleAccessPoints'][$moduleName] = [
        'module' => $moduleName,
        'ssl' => $ssl,
        'port' => $port
    ];
    $validModules++;
}

if (!$validModules) {
    echo "No valid Framelix modules given in FRAMELIX_MODULES environment variable. Aborting.";
} else {
    file_put_contents($nginxSitesPath, implode("\n\n", $contents));
}
file_put_contents($envConfigPath, json_encode($envConfig));

exit($allValid && $validModules > 0 ? 0 : 1);