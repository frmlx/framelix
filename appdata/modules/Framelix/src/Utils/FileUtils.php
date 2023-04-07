<?php

namespace Framelix\Framelix\Utils;

use function array_merge;
use function dirname;
use function is_dir;
use function preg_match;
use function realpath;
use function rmdir;
use function scandir;
use function str_replace;
use function strlen;
use function substr;
use function unlink;

use const SCANDIR_SORT_ASCENDING;

/**
 * File utilities for frequent tasks
 */
class FileUtils
{
    /**
     * Get a filepath to the userdata directory, doesn't matter if the file exist
     * @param string|null $filePath The relative filepath starting from the dedicated userdata folder
     * @param string $module
     * @param bool $public Is this file available via URL (public) or only private on disk
     * @param bool $autoCreateFolder If true, creates the folder hierarchy (if not exist) to the new file path
     * @return string
     */
    public static function getUserdataFilepath(
        ?string $filePath,
        bool $public,
        string $module = FRAMELIX_MODULE,
        bool $autoCreateFolder = true
    ): string {
        $userdataFolder = FRAMELIX_USERDATA_FOLDER . "/$module/" . ($public ? "public" : "private") . "/" . $filePath;
        $dir = dirname($userdataFolder);
        if ($autoCreateFolder && !is_dir($dir)) {
            mkdir($dir, recursive: true);
            clearstatcache();
        }
        return $userdataFolder;
    }

    /**
     * Normalize path
     * Converting all directory separators to /
     * @param string $path
     * @param bool $realpath If true, then apply realpath() to it (returns null if file not exist)
     * @return string|null
     */
    public static function normalizePath(string $path, bool $realpath = false): ?string
    {
        if ($realpath) {
            $path = realpath($path);
        }
        return str_replace("\\", "/", $path);
    }

    /**
     * Get path to modules root folder
     * @param string $module
     * @return string
     */
    public static function getModuleRootPath(string $module): string
    {
        return self::normalizePath(dirname(__DIR__, 3) . "/$module");
    }

    /**
     * Get relative path to given base path
     * By defaults its the project root, the folder where the folder "modules" is in
     * @param string $file
     * @param string $base
     * @return string
     */
    public static function getRelativePathToBase(string $file, string $base = __DIR__ . "/../../../../"): string
    {
        $path = realpath($file);
        $base = realpath($base);
        return self::normalizePath(substr($path, strlen($base) + 1));
    }


    /**
     * Get files in a directory and return flat file list with absolute paths
     * @param string $directory
     * @param string|null $regex
     * @param bool $recursive
     * @param bool $includeDirectoriesPaths
     * @param int $sortOrder SCANDIR_SORT_NONE,SCANDIR_SORT_ASCENDING, SCANDIR_SORT_DESCENDING
     * @return string[]
     */
    public static function getFiles(
        string $directory,
        ?string $regex = null,
        bool $recursive = false,
        bool $includeDirectoriesPaths = false,
        int $sortOrder = SCANDIR_SORT_ASCENDING
    ): array {
        $files = [];
        if (!is_dir($directory)) {
            return $files;
        }
        $directory = self::normalizePath($directory);
        $scan = scandir($directory, $sortOrder);
        foreach ($scan as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $path = $directory . "/" . $file;
            if (is_dir($path)) {
                if ($includeDirectoriesPaths) {
                    $files[] = $path;
                }
                if ($recursive) {
                    $files = array_merge($files, self::getFiles($path, $regex, $recursive));
                }
            } elseif (!$regex || preg_match($regex, $path)) {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Delete directory including all files in it and also the directory itself if provided
     * @param string $directory
     * @param bool $includeSelf
     * @return void
     */
    public static function deleteDirectory(string $directory, bool $includeSelf = true): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }
            $path = $directory . "/" . $file;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}