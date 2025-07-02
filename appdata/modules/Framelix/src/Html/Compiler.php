<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;

use function array_combine;
use function base64_encode;
use function basename;
use function file_get_contents;
use function filemtime;
use function filesize;
use function implode;
use function is_file;
use function md5;
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
        // already compiled, skip
        if (isset(self::$cache['compiled-' . $module])) {
            return null;
        }
        // no compile in production or missing module
        if (!Config::$devMode || !isset(Framelix::$registeredModules[$module])) {
            return null;
        }
        // no compile in an async request
        if (Request::isAsync()) {
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
        $existingDistFiles = array_merge(
            FileUtils::getFiles("$moduleRoot/public/dist/css", "~\.css$~", true),
            FileUtils::getFiles("$moduleRoot/public/dist/js", "~\.js$~", true)
        );
        $existingDistFiles = array_combine($existingDistFiles, $existingDistFiles);
        $returnDistFiles = [];
        foreach ($compilerFileBundles as $bundle) {
            $compileFiles = $bundle->getFiles();
            $distFilePath = $bundle->getGeneratedBundleFilePath();
            $metaFilePath = $distFilePath . ".hash.txt";
            $metadataWrite = md5(JsonUtils::encode([
                'bundle' => $bundle,
                'compileFiles' => $compileFiles,
            ]));
            if (!is_file($metaFilePath) || file_get_contents($metaFilePath) !== $metadataWrite) {
                $forceUpdate = true;
            }

            // check if there need to be an update based on filetimes
            $compilerRequired = true;
            if (!$compileFiles) {
                $compilerRequired = is_file($distFilePath) && filesize($distFilePath);
            } elseif (!$forceUpdate && is_file($distFilePath)) {
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
                'options' => $bundleOptions,
            ];
            $shell = Shell::prepare(
                "bun {*}",
                [
                    __DIR__ . "/../../nodejs/compiler.js",
                    base64_encode(JsonUtils::encode($cmdParams)),
                ]
            );
            $shell->execute();
            if ($shell->status) {
                throw new FatalError(implode("\n", $shell->output));
            }
            $returnDistFiles[] = $distFilePath;
            touch($distFilePath);
            FileUtils::writeToFile($metaFilePath, $metadataWrite);
            Toast::success(basename($distFilePath) . " compiled successfully");
        }
        // delete old files
        foreach ($existingDistFiles as $existingDistFile) {
            unlink($existingDistFile);
        }
        return $returnDistFiles;
    }

}