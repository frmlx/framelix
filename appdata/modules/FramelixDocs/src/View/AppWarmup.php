<?php

namespace Framelix\FramelixDocs\View;

use Framelix\FramelixDocs\Console;

class AppWarmup extends \Framelix\Framelix\View
{
    protected string|bool $accessRole = "*";
    protected bool $devModeOnly = true;

    public function onRequest(): void
    {
        // just run the app warmup command - required for unit tests
        \Framelix\Framelix\Console::appWarmup();
        Console::appWarmup();
    }
}