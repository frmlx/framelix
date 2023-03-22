<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

const FRAMELIX_APPDATA_FOLDER = "/framelix/appdata";
require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!Framelix::isCli()) {
    View::loadViewForCurrentUrl();
}