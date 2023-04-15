<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\Mutex;

class Cron extends Console
{
    public static function runCron(): void
    {
        if (!Mutex::isLocked('framelix-hourly-cron', 3600)) {
            Mutex::create('framelix-hourly-cron');
        }
        if (!Mutex::isLocked('framelix-hourly-half-day', 43200)) {
            Mutex::create('framelix-hourly-half-day');
            // delete old logs
            foreach (Config::$enabledBuiltInSystemEventLogsKeepDays as $category => $days) {
                $days = (int)$days;
                if (!$days <= 0) {
                    continue;
                }
                $logs = SystemEventLog::getByCondition(
                    'category = {0} && DATE(createTime) < {1}',
                    [$category, Date::create("now -  $days days")]
                );
                Storable::deleteMultiple($logs);
            }
        }
    }
}