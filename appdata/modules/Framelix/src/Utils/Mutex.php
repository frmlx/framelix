<?php

namespace Framelix\Framelix\Utils;

use Throwable;

use function file_exists;
use function file_put_contents;
use function filemtime;
use function sleep;
use function time;
use function unlink;

/**
 * Mutex
 * Provide an easy way to lock/release exclusive execution for tasks
 */
class Mutex
{

    /**
     * Create a mutex for given name
     * If mutex with this name already exist, it does update the mutex start time
     * @param string $name The name is in the namespace of the FRAMELIX_MODULE, so each module can have the same name
     *     without an interferance
     */
    public static function create(string $name): void
    {
        $file = FileUtils::getUserdataFilepath("mutex/$name.mutex", false);
        // retry a few times as it is possible that file is currently IO blocked by other thread
        $count = 1;
        while ($count++ < 3) {
            try {
                file_put_contents($file, time());
                break;
            } catch (Throwable $e) {
                sleep(1);
            }
        }
    }

    /**
     * Check if a mutex with given name exist
     * @param string $name
     * @param int|null $maxLifetime Seconds from start of mutex - If set, it will return true when mutex is older than
     *     max lifetime Should be used when you not want a deadlock if a job cant release a mutex because of errors and
     *     mutex is never released
     * @return int 0 = not locked, -1 = inifinite locked (not maxlifetime given), > 0 Seconds how long the mutex is
     *     still locked
     */
    public static function isLocked(string $name, ?int $maxLifetime = null): int
    {
        $file = FileUtils::getUserdataFilepath("mutex/$name.mutex", false);
        if (!file_exists($file)) {
            return 0;
        }
        $time = (int)filemtime($file);
        if ($maxLifetime > 0) {
            $lifetimeRemains = $time - (time() - $maxLifetime);
            return $lifetimeRemains > 0 ? $lifetimeRemains : 0;
        }
        return -1;
    }

    /**
     * Release a mutex for given name
     * @param string $name
     */
    public static function release(string $name): void
    {
        $file = FileUtils::getUserdataFilepath("mutex/$name.mutex", false);
        if (file_exists($file)) {
            unlink($file);
        }
    }

}