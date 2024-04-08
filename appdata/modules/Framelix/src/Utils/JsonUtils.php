<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Network\Response;
use Stringable;
use Throwable;

use function file_exists;
use function is_object;
use function json_encode;

use const FRAMELIX_APPDATA_FOLDER;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * Json utilities for frequent tasks
 */
class JsonUtils
{

    private static array $cache = [];

    /**
     * Get package json data
     * @param string|null $module If null, then take package.json of app root
     * @return array|null Null if no package.json exists
     */
    public static function getPackageJson(?string $module): ?array
    {
        $cacheKey = (string)$module;
        if (ArrayUtils::keyExists(self::$cache, $cacheKey)) {
            return self::$cache[$cacheKey];
        }
        if ($module === null) {
            $path = FRAMELIX_APPDATA_FOLDER . "/package.json";
        } else {
            $path = FRAMELIX_APPDATA_FOLDER . "/modules/$module/package.json";
        }
        if (!file_exists($path)) {
            self::$cache[$cacheKey] = null;
            return null;
        }
        self::$cache[$cacheKey] = self::readFromFile($path);
        return self::$cache[$cacheKey];
    }

    /**
     * Write to file
     * @param string $path
     * @param mixed $data
     * @param bool $prettyPrint
     */
    public static function writeToFile(string $path, mixed $data, bool $prettyPrint = false): void
    {
        FileUtils::writeToFile($path, self::encode($data, $prettyPrint));
    }

    /**
     * Read from file
     * @param string $path
     * @return mixed
     */
    public static function readFromFile(string $path): mixed
    {
        return self::decode(file_get_contents($path));
    }

    /**
     * Output given data and set correct content type
     * @param mixed $data
     */
    public static function output(mixed $data): void
    {
        Response::header("content-type: application/json");
        echo self::encode($data);
    }

    /**
     * Encode
     * @param mixed $data
     * @param bool $prettyPrint
     * @param bool $convertSpecialChars If true, convert html special chars to unicode representation
     * @return string
     */
    public static function encode(mixed $data, bool $prettyPrint = false, bool $convertSpecialChars = false): string
    {
        $options = JSON_THROW_ON_ERROR;
        if ($prettyPrint) {
            $options = $options | JSON_PRETTY_PRINT;
        }
        if ($convertSpecialChars) {
            $options = $options | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP;
        }
        if (Config::$devMode) {
            try {
                return json_encode($data, $options);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if ($data instanceof Stringable) {
                    $data = (string)$data;
                }
                if (!is_object($data)) {
                    $msg .= ' in ' . json_encode($data);
                }
                throw new FatalError($msg);
            }
        }
        return json_encode($data, $options);
    }

    /**
     * Decode
     * @param string $data
     * @return mixed
     */
    public static function decode(string $data): mixed
    {
        if (Config::$devMode) {
            try {
                return json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
            } catch (Throwable $e) {
                throw new FatalError($e->getMessage() . " in string: " . $data);
            }
        }
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

}