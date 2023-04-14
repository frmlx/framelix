<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\Mutex;

use function array_chunk;
use function ceil;
use function filemtime;
use function unlink;

use const FRAMELIX_USERDATA_FOLDER;
use const SORT_DESC;
use const SORT_NUMERIC;

class Cron extends Console
{
    public static function runCron(): void
    {
        if (self::getParameter('forceBackup')) {
            self::automaticAppDbBackup();
        }
        if (!Mutex::isLocked('framelix-hourly-cron', 3600)) {
            Mutex::create('framelix-hourly-cron');
            if ((int)date("H") === 3) {
                self::automaticAppDbBackup();
            }
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

    private static function automaticAppDbBackup(): void
    {
        $backupFiles = FileUtils::getFiles(FRAMELIX_USERDATA_FOLDER . "/backups", "~/auto_.*\.sql$~");
        $dayDiffSinceLastBackup = 999;
        if ($backupFiles) {
            $arr = [];
            foreach ($backupFiles as $backupFile) {
                $arr[] = [
                    'path' => $backupFile,
                    'time' => filemtime($backupFile)
                ];
            }
            ArrayUtils::sort($arr, "time", [SORT_DESC, SORT_NUMERIC]);
            $dayDiffSinceLastBackup = ceil((time() - reset($arr)['time']) / 86400);
            if (Config::$automaticSqlBackupMaxLogs > 0) {
                $chunks = array_chunk($arr, Config::$automaticSqlBackupMaxLogs - 1);
                if (count($chunks) > 1) {
                    unset($chunks[0]);
                    foreach ($chunks as $arr) {
                        foreach ($arr as $row) {
                            unlink($row['path']);
                        }
                    }
                }
            }
        }
        if ((Config::$automaticSqlBackupInterval > 0 && $dayDiffSinceLastBackup >= Config::$automaticSqlBackupInterval) || self::getParameter(
                'forceBackup'
            )) {
            self::backupSqlDatabases("sql_auto_");
        }
    }
}