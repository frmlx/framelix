<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;

use function array_combine;
use function array_values;
use function base64_encode;
use function basename;
use function file_exists;
use function filemtime;
use function implode;
use function in_array;
use function realpath;
use function unlink;

class Compiler
{
    /**
     * Internal cache
     * Not modify it, it is public just for unit tests
     * @var array
     */
    public static array $cache = [];

    /**
     * Compile js and scss files for given module
     * @param string $module
     * @param bool $forceUpdate
     * @return string[]|null Return array of compiled dist file paths
     */
    public static function compile(string $module, bool $forceUpdate = false): ?array
    {
        // no compile in production or missing module
        if (!Config::$devMode || !isset(Framelix::$registeredModules[$module])) {
            return null;
        }
        // already compiled, skip
        if (isset(self::$cache['compiled-' . $module])) {
            return null;
        }
        self::$cache['compiled-' . $module] = true;
        $compilerFileBundles = Config::$compilerFileBundles;
        foreach ($compilerFileBundles as $key => $bundle) {
            if ($bundle->module !== $module) {
                unset($compilerFileBundles[$key]);
            }
        }
        if (!$compilerFileBundles) {
            return null;
        }
        // meta file will store previous compiler data
        // if anything changes, then we need to force an update
        $moduleRoot = FileUtils::getModuleRootPath($module);
        $existingDistFiles = FileUtils::getFiles("$moduleRoot/public/dist", "~\.(js|css)$~", true);
        $existingDistFiles = array_combine($existingDistFiles, $existingDistFiles);
        $metaFilePath = "$moduleRoot/public/dist/_meta.json";
        $compilerFileBundlesJson = JsonUtils::decode(JsonUtils::encode($compilerFileBundles));
        $metadataChanged = false;
        if (!file_exists($metaFilePath) || JsonUtils::readFromFile($metaFilePath) !== $compilerFileBundlesJson) {
            $forceUpdate = true;
            $metadataChanged = true;
        }
        $returnDistFiles = [];
        foreach ($compilerFileBundles as $bundle) {
            $files = [];
            if ($bundle->type === 'scss') {
                $bootstrapFile = $moduleRoot . "/scss/_compiler-bootstrap.scss";
                if (file_exists($bootstrapFile)) {
                    $files[] = $bootstrapFile;
                }
            }
            $distFilePath = $bundle->getGeneratedBundleFilePath();
            foreach ($bundle->entries as $row) {
                if ($row['type'] === 'file') {
                    $files[] = FileUtils::getModuleRootPath($module) . "/" . $row['path'];
                } elseif ($row['type'] === 'folder') {
                    $path = FileUtils::getModuleRootPath($module) . "/" . $row['path'];
                    $extensions = $bundle->type === 'js' ? 'js' : "(css|scss)";
                    $folderFiles = FileUtils::getFiles(
                        $path,
                        "~\.$extensions$~",
                        $row['recursive'] ?? false
                    );
                    if (isset($row['ignoreFilenames'])) {
                        foreach ($folderFiles as $key => $file) {
                            if (in_array(basename($file), $row['ignoreFilenames'])) {
                                unset($folderFiles[$key]);
                            }
                        }
                    }
                    $files = array_merge(
                        $files,
                        $folderFiles
                    );
                }
            }
            // remove dupes
            $compileFiles = [];
            foreach ($files as $file) {
                $file = realpath($file);
                if (!isset($compileFiles[$file])) {
                    $compileFiles[$file] = $file;
                }
            }
            $compileFiles = array_values($compileFiles);
            // check if there need to be an update based on filetimes
            $compilerRequired = true;
            if (!$forceUpdate && file_exists($distFilePath)) {
                $compilerRequired = false;
                $distFileTimestamp = filemtime($distFilePath);
                foreach ($compileFiles as $file) {
                    if (filemtime($file) > $distFileTimestamp) {
                        $compilerRequired = true;
                        break;
                    }
                }
            }
            unset($existingDistFiles[$distFilePath]);
            // skip if no files exist
            if (!$files) {
                continue;
            }
            // skip if we are already up-to-date
            if (!$compilerRequired && !$forceUpdate) {
                continue;
            }
            // pass to nodejs compiler script
            $bundleOptions = JsonUtils::decode(JsonUtils::encode($bundle));
            unset($bundleOptions['entries']);
            $cmdParams = [
                'type' => $bundle->type,
                'distFilePath' => $distFilePath,
                'files' => $compileFiles,
                'options' => $bundleOptions
            ];
            $shell = Shell::prepare(
                "node {*}",
                [
                    __DIR__ . "/../../nodejs/compiler.js",
                    base64_encode(JsonUtils::encode($cmdParams))
                ]
            );
            $shell->execute();
            if ($shell->status) {
                throw new FatalError(implode("\n", $shell->output));
            }
            $returnDistFiles[] = $distFilePath;
            touch($distFilePath);
            Toast::success(basename($distFilePath) . " compiled successfully");
        }
        // delete old files
        foreach ($existingDistFiles as $existingDistFile) {
            unlink($existingDistFile);
        }
        if ($metadataChanged) {
            // write compiler data to meta file
            JsonUtils::writeToFile($metaFilePath, $compilerFileBundlesJson);
        }
        return $returnDistFiles;
    }
}