<?php

namespace Framelix\FramelixTests;

use Framelix\Framelix\Utils\Shell;

use function file_exists;
use function file_get_contents;

use const FRAMELIX_MODULE;

class Console extends \Framelix\Framelix\Console
{
    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        Shell::prepare("mysql -u root -papp -e 'CREATE DATABASE IF NOT EXISTS unittests'")->execute();
        $userConfigFile = \Framelix\Framelix\Config::getUserConfigFilePath();
        if (!file_exists($userConfigFile)) {
            \Framelix\Framelix\Framelix::createInitialUserConfig(FRAMELIX_MODULE, 'test',
                '127.0.0.1:' . file_get_contents('/framelix/system/port_FramelixTests'), '');
        }
        return 0;
    }
}