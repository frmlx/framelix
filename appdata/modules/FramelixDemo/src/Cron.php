<?php

namespace Framelix\FramelixDemo;

use Framelix\Framelix\Utils\Mutex;

class Cron extends Console
{
    public const CLEANUP_MUTEX_NAME = 'cleanup';
    public const CLEANUP_MUTEX_LIFETIME = 3600;

    public static function runCron(): void
    {
        if (!Mutex::isLocked(self::CLEANUP_MUTEX_NAME, self::CLEANUP_MUTEX_LIFETIME)) {
            Mutex::create(self::CLEANUP_MUTEX_NAME);
            Console::cleanupDemoData();
        }
    }
}