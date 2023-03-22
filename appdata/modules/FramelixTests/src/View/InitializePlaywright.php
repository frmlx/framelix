<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\Console;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View;

use function sleep;

class InitializePlaywright extends View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        // this does delete all app and userdata
        // it is used as the starting point for playwright tests so every test starts totally fresh
        Shell::prepare('framelix_reset_app -y')->execute();
        sleep(3);
        Console::appWarmup();

        echo "App Reset done";
    }
}