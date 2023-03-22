<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

define("FRAMELIX_MODULE", trim(file_get_contents('/framelix/system/MODULE')));
define("FRAMELIX_APP_ROOT", str_replace("\\", "/", dirname(__DIR__, 3)));

require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!Framelix::isCli()) {
    View::loadViewForCurrentUrl();
}