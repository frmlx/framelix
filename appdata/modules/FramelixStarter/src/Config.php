<?php

namespace Framelix\FramelixStarter;

class Config
{
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FRAMELIX_DBDATA_FOLDER . "/sqlite_" . FRAMELIX_MODULE . ".db"
        );
    }
}