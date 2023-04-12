<?php

namespace Framelix\FramelixDocs;

use Framelix\Framelix\Storable\Mutex;

class Cron extends Console
{
    public static function runCron(): void
    {
        if (!Mutex::isLocked('framelixdocs-hourly', 3600)) {
            Mutex::create('framelixdocs-hourly');
            Console::cleanupDemoData();
        }
    }
}