<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;

use function md5;

/**
 * Very simple ip based brute force protection
 * Choose limits very high to prevent false positive from same networks (Big companies with lots of clients under same ip)
 * @property string $idHash
 * @property mixed $logs
 * @property DateTime $lastLog
 */
class BruteForceProtection extends Storable
{

    public const int MAX_ENTRY_AGE = 86400;

    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['idHash']->length = 32;
        $selfStorableSchema->addIndex('idHash', 'unique');
        $selfStorableSchema->addIndex('lastLog', 'index');
    }

    public static function cleanup(): void
    {
        self::deleteMultiple(self::getByCondition('lastLog < {0}', [DateTime::create("now - " . self::MAX_ENTRY_AGE . " seconds")]));
    }

    /**
     * Reset status for current ip and id
     * @param string $id
     * @param string|null $connectionId Database connection id to use
     * @return void
     */
    public static function reset(
        string $id,
        ?string $connectionId = null
    ): void {
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        $entry?->delete();
    }

    /**
     * Log and attempt
     * @param string $id
     * @param string|DateTime $logTimestamp The timestamp to use for this log
     * @param string|null $connectionId Database connection id to use
     * @return void
     */
    public static function logAttempt(
        string $id,
        string|DateTime $logTimestamp = "now",
        ?string $connectionId = null,
    ): void {
        self::cleanup();
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        if (!$entry) {
            $entry = new self();
            if ($connectionId) {
                $entry->connectionId = $connectionId;
            }
            $entry->idHash = $idHash;
            $entry->logs = [];
        }
        $logs = $entry->logs;
        $logs[] = DateTime::create($logTimestamp)->getTimestamp();
        $entry->logs = $logs;
        $entry->lastLog = DateTime::create($logTimestamp);
        $entry->store();
    }

    /**
     * Check if given current client ip and given id is blocked
     * @param string $id
     * @param bool $addToast If true, it will add an error toast which describes how long the user must wait
     * @param int $maxAttempts Maximum allowed attempts in $withinTimeRange
     * @param int $timeRangeSpan Number of seconds (between $timeRangeEnd - $withinTimeRange and $timeRangeEnd) in which the attempts are counted
     * @param int $mustWaitSecondsIfBlocked Number of seconds the user must wait after last log if he is blocked
     * @param string|DateTime $timeRangeEnd Count attempts until this time
     * @param string|null $connectionId Database connection id to use
     * @return bool Return false when is not blocked
     */
    public static function isBlocked(
        string $id,
        bool $addToast = true,
        int $maxAttempts = 60,
        int $timeRangeSpan = 60,
        int $mustWaitSecondsIfBlocked = 60,
        string|DateTime $timeRangeEnd = "now",
        ?string $connectionId = null,
    ): bool {
        $idHash = self::getIdHash($id);
        $entry = self::getByConditionOne('idHash = {0}', [$idHash], connectionId: $connectionId);
        if (!$entry || !$entry->logs) {
            return false;
        }
        // cleanup logs older then max timespan
        $logs = $entry->logs;
        $count = 0;
        $logsModified = false;
        $timeRangeEndTimestamp = DateTime::create($timeRangeEnd)->getTimestamp();
        $timeRangeStartTimestamp = $timeRangeEndTimestamp - $timeRangeSpan;
        foreach ($logs as $key => $log) {
            if ($log >= $timeRangeStartTimestamp && $log <= $timeRangeEndTimestamp) {
                $count++;
            }
            if ($log < $timeRangeStartTimestamp) {
                unset($logs[$key]);
                $logsModified = true;
            }
        }
        if ($logsModified) {
            $entry->logs = $logs;
            $entry->store();
        }
        if ($count >= $maxAttempts) {
            if ($addToast) {
                $waitUntil = $entry->lastLog;
                $waitUntil->modify("+ $mustWaitSecondsIfBlocked seconds");
                Toast::error(
                    Lang::get(
                        '__framelix_wait_for_unblock__',
                        [$waitUntil->getRelativeTimeUnits(DateTime::create($timeRangeEnd))]
                    )
                );
            }
            return true;
        }
        return false;
    }

    /**
     * Get id hash
     * @param string $id
     * @return string
     */
    private static function getIdHash(string $id): string
    {
        return md5(Request::getClientIp() . "-" . $id);
    }

}