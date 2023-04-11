<?php

namespace Framelix\FramelixStarter;

use const FRAMELIX_USERDATA_FOLDER;

class Config
{
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FRAMELIX_USERDATA_FOLDER . "/sqlite_" . FRAMELIX_MODULE . ".db"
        );
    }
}