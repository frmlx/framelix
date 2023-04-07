<?php

namespace Framelix\FramelixStarter;

use Framelix\Framelix\Utils\FileUtils;

class Config
{
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FileUtils::getUserdataFilepath("database.db", false)
        );
    }
}