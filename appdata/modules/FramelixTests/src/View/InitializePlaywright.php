<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\Console;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View;

use function sleep;

use const FRAMELIX_MODULE;

class InitializePlaywright extends View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        // this does delete all app and userdata
        // it is used as the starting point for playwright tests so every test starts totally fresh
        $db = Mysql::get();
        $db->query('DROP DATABASE `' . FRAMELIX_MODULE . '`');
        $db->query('CREATE DATABASE `' . FRAMELIX_MODULE . '`');
        $db->query('USE `' . FRAMELIX_MODULE . '`');
        Console::appWarmup();
        // remove configs
        Shell::prepare('rm /framelix/userdata/*/private/config/01-core.php')->execute();
        Shell::prepare('rm /framelix/userdata/*/private/config/02-ui.php')->execute();
        sleep(3);

        echo "App Reset done";
    }
}