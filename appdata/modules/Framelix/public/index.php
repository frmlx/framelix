<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

const FRAMELIX_APPDATA_FOLDER = "/framelix/appdata";
const FRAMELIX_USERDATA_FOLDER = "/framelix/userdata";
require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!Framelix::isCli()) {
    View::loadViewForCurrentUrl();
}