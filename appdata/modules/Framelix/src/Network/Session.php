<?php

namespace Framelix\Framelix\Network;

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
     * Each session name have it's own file
     * @var string
     */
    public static string $sessionName = "fsid_" . FRAMELIX_MODULE;

    private static array $cache = [];

    /**
     * Cleanup sessions that are older than than max lifetime
     * @return void
     */
    public static function cleanup(): void
    {
        $sessionBaseFolder = FileUtils::getUserdataFilepath("sessions", false, autoCreateFolder: false);
        if (is_dir($sessionBaseFolder)) {
            $folders = scandir($sessionBaseFolder);
            $date = date("ymd");
            foreach ($folders as $folder) {
                if (str_starts_with($folder, ".") || strlen($folder) !== 6) {
                    continue;
                }
                $dateFolder = (int)$folder;
                if (($date - $dateFolder) > self::MAX_LIFETIME_DAYS) {
                    FileUtils::deleteDirectory($sessionBaseFolder, $folder);
                }
            }
        }
    }

    /**
     * Get session file path
     * @param bool $readOnly
     * @return string|null Return the path if exist
     */
    public static function getSessionFilePath(bool $readOnly): ?string
    {
        $cacheKey = self::$sessionName . "_path_" . (int)$readOnly;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        self::$cache[$cacheKey] = null;
        $date = date("ymd");
        if ($readOnly) {
            $sessionId = self::$sessionName . "_" . Cookie::get(self::$sessionName);
            if (!Cookie::get(self::$sessionName)) {
                return self::$cache[$cacheKey];
            }
            $path = FileUtils::getUserdataFilepath("sessions/$date/$sessionId.json", false, autoCreateFolder: false);
            if (is_file($path)) {
                self::$cache[$cacheKey] = $path;
            }
            return self::$cache[$cacheKey];
        }
        $sessionId = Cookie::get(self::$sessionName);
        if (!$sessionId) {
            $sessionId = RandomGenerator::getRandomString(64);
            do {
                $path = FileUtils::getUserdataFilepath("sessions/$date/" . self::$sessionName . "_$sessionId.json",
                    false);
            } while (is_file($path));
            Cookie::set(self::$sessionName, $sessionId);
        }
        self::$cache[$cacheKey] = FileUtils::getUserdataFilepath("sessions/$date/" . self::$sessionName . "_$sessionId.json",
            false);
        return self::$cache[$cacheKey];
    }

    /**
     * Get a session value
     * @param string $name The session key name
     * @return mixed|null
     */
    public static function get(string $name): mixed
    {
        if (!self::getSessionFilePath(true)) {
            return null;
        }
        self::loadSessionDataIntoCache();
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
        self::loadSessionDataIntoCache();
        if ($value === null) {
            unset(self::$cache[self::$sessionName . '_data'][$name]);
        } else {
            self::$cache[self::$sessionName . '_data'][$name] = $value;
        }
        JsonUtils::writeToFile(self::getSessionFilePath(false), self::$cache[self::$sessionName . '_data']);
    }

    private static function loadSessionDataIntoCache(): void
    {
        if (array_key_exists(self::$sessionName . '_data', self::$cache)) {
            return;
        }
        self::$cache[self::$sessionName . '_data'] = [];
        $path = self::getSessionFilePath(false);
        if (is_file($path)) {
            self::$cache[self::$sessionName . '_data'] = JsonUtils::readFromFile($path);
        }
    }
}