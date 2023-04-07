<?php

use Framelix\Framelix\Config;

include __DIR__ . "/../public/index.php";

// disable system event log, it should be tested explicitely
Config::$enabledBuiltInSystemEventLogs = [];
// use a fixed timezone
ini_set("date.timezone", "Europe/Berlin");
// disable time limit
ini_set("max_execution_time", -1);

spl_autoload_register(function ($className) {
    $className = str_replace("\\", "/", $className);
    $path = __DIR__ . "/$className.php";
    if (file_exists($path)) {
        require_once $path;
    }
});