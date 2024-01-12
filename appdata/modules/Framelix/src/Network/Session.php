<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;

use function array_key_exists;
use function date;
use function is_dir;
use function is_file;
use function scandir;
use function str_starts_with;

use const FRAMELIX_MODULE;

/**
 * Framelix custom session
 * Will work like normal $_SESSION but is protected against cookie id manipulation
 */
class Session
{

    /**
     * How long should sessions been kept on disk
     */
    public const MAX_LIFETIME_DAYS = 3;

    /**
     * The session name
     * Each session name have its own file
     * @var string
     */
    public static string $sessionName = "fsid_" . FRAMELIX_MODULE;

    private static array $cache = [];

    /**
     * Cleanup sessions that are older than max lifetime
     * @param DateTime|null $dateNow If set, use another reference date (default is today)
     * @return void
     */
    public static function cleanup(?DateTime $dateNow = null): void
    {
        $sessionBaseFolder = FileUtils::getUserdataFilepath("sessions", false, autoCreateFolder: false);
        if (is_dir($sessionBaseFolder)) {
            $folders = scandir($sessionBaseFolder);
            $date = DateTime::create($dateNow ?? 'now')->format('ymd');
            foreach ($folders as $folder) {
                if (str_starts_with($folder, ".") || strlen($folder) !== 6) {
                    continue;
                }
                $dateFolder = (int)$folder;
                if (($date - $dateFolder) > self::MAX_LIFETIME_DAYS) {
                    FileUtils::deleteDirectory($sessionBaseFolder . "/" . $folder);
                }
            }
        }
    }

    /**
     * Clear all session values
     */
    public static function clear(): void
    {
        unset(self::$cache[self::$sessionName . '_data']);
        $path = self::getSessionFilePath();
        if ($path && is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Get the whole session data array
     * @return array|null
     */
    public static function getAll(): ?array
    {
        self::prepare(false);
        return self::$cache[self::$sessionName . '_data'] ?? null;
    }

    /**
     * Get a session value
     * @param string $name The session key name
     * @return mixed|null
     */
    public static function get(string $name): mixed
    {
        self::prepare(false);
        return self::$cache[self::$sessionName . '_data'][$name] ?? null;
    }

    /**
     * Set a session value
     * @param string $name The session key name
     * @param mixed $value Null will unset the key, can be any json serializable value
     */
    public static function set(
      string $name,
      mixed $value
    ): void {
        self::prepare(true);
        if ($value === null) {
            unset(self::$cache[self::$sessionName . '_data'][$name]);
        } else {
            self::$cache[self::$sessionName . '_data'][$name] = $value;
        }
        JsonUtils::writeToFile(
          self::getSessionFilePath(null, true),
          self::$cache[self::$sessionName . '_data']
        );
    }

    /**
     * Get the sessions file path
     * @param string|null $sessionId If null, it will try to get the saved cookie session id
     * @param bool $createFolderIfNotExist
     * @return string|null
     */
    public static function getSessionFilePath(?string $sessionId = null, bool $createFolderIfNotExist = false): ?string
    {
        $sessionId = $sessionId ?? Cookie::get(self::$sessionName);
        if(!$sessionId){
            return null;
        }
        $date = date("ymd");
        $sessionFilename = self::$sessionName . "_" . $sessionId . ".json";
        return FileUtils::getUserdataFilepath(
          "sessions/$date/$sessionFilename",
          false,
          autoCreateFolder: $createFolderIfNotExist
        );
    }

    private static function prepare(bool $setSessionCookie): void
    {
        $sessionId = Cookie::get(self::$sessionName);
        // no saved session id, skip
        if (!$sessionId && !$setSessionCookie) {
            return;
        }
        if (!$sessionId) {
            $sessionId = RandomGenerator::getRandomString(64);
            do {
                $path = self::getSessionFilePath($sessionId, true);
            } while (is_file($path));
            Cookie::set(self::$sessionName, $sessionId);
        }
        $cacheKey = self::$sessionName . '_data';
        if (!array_key_exists($cacheKey, self::$cache)) {
            self::$cache[$cacheKey] = [];
            $path = self::getSessionFilePath($sessionId, true);
            if (is_file($path)) {
                self::$cache[$cacheKey] = JsonUtils::readFromFile($path);
            }
        }
    }

}