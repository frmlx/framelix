<?php

use Framelix\Framelix\Framelix;
use Framelix\Framelix\View;

/** The folder that contain all appdata */
const FRAMELIX_APPDATA_FOLDER = "/framelix/appdata";
/** The folder that does contain all user data including configs */
const FRAMELIX_USERDATA_FOLDER = "/framelix/userdata";
/** The folder that does contain database data */
const FRAMELIX_DBDATA_FOLDER = "/framelix/dbdata";
/** The temporary folder to store files that can be deleted at any time */
const FRAMELIX_TMP_FOLDER = FRAMELIX_USERDATA_FOLDER . "/tmp";

require __DIR__ . "/../src/Framelix.php";
Framelix::init();

if (!Framelix::isCli()) {
    View::loadViewForCurrentUrl();
}