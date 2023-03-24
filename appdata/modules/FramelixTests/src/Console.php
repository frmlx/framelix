<?php

namespace Framelix\FramelixTests;

use Framelix\Framelix\Utils\Shell;

class Console extends \Framelix\Framelix\Console
{
    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        Shell::prepare("mysql -uroot -papp -e 'DROP DATABASE IF EXISTS `unittests`; CREATE DATABASE `unittests`;'")->execute();
        // install dev libs for tests
        Shell::prepare("cd /framelix/appdata && composer install")->execute();
        return 0;
    }
}