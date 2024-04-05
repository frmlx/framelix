<?php

namespace Framelix\Framelix\Html\TypeDefs;

use Framelix\Framelix\Config;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\PhpDocParser;
use JsonSerializable;
use ReflectionClass;

use function basename;
use function file_put_contents;
use function filemtime;
use function implode;
use function str_replace;
use function substr;

use const FRAMELIX_APPDATA_FOLDER;

/**
 * Type definitions without logic
 * This classes can be added to the JS compiler, which automatically generates a js doc file in the "dev" folder of the module
 * Just for good autocompletion in both PHP and JS
 */
abstract class BaseTypeDef implements JsonSerializable
{
    public static function compile(string $module): void
    {
        // no compile in production or missing module
        if (!Config::$devMode || !isset(Framelix::$registeredModules[$module])) {
            return;
        }
        // no compile in an async request
        if (Request::isAsync()) {
            return;
        }
        $distFolder = FRAMELIX_APPDATA_FOLDER . "/modules/$module/public/dist/typedefs";
        $srcTypeDefFiles = FileUtils::getFiles(FRAMELIX_APPDATA_FOLDER . "/modules/$module/src/Html/TypeDefs",
            "~.*\.php~");
        $jsTypeDefFiles = FileUtils::getFiles($distFolder, "~.*\.js~");
        $lastTimeStampSrc = 0;
        foreach ($srcTypeDefFiles as $key => $typeDefFile) {
            $basename = basename($typeDefFile);
            if ($basename === 'BaseTypeDef.php') {
                unset($srcTypeDefFiles[$key]);
                continue;
            }
            $timestamp = filemtime($typeDefFile);
            if ($timestamp > $lastTimeStampSrc) {
                $lastTimeStampSrc = $timestamp;
            }
        }
        $lastTimeStampJs = 0;
        foreach ($jsTypeDefFiles as $typeDefFile) {
            $timestamp = filemtime($typeDefFile);
            if ($timestamp > $lastTimeStampJs) {
                $lastTimeStampJs = $timestamp;
            }
        }
        // src files are older then js files, no update required
        if ($lastTimeStampJs >= $lastTimeStampSrc) {
            return;
        }
        foreach ($srcTypeDefFiles as $typeDefFile) {
            /** @var static $class */
            $class = ClassUtils::getClassNameForFile($typeDefFile);
            $reflection = new ReflectionClass($class);
            $jsFileName = substr(basename($typeDefFile), 0, -4) . ".js";
            $jsFilePath = $distFolder . "/$jsFileName";
            $props = $reflection->getProperties();
            $jsData = "/**\n * @typedef {Object} " . $class::getJsTypeName() . "\n";
            foreach ($props as $prop) {
                $propName = $prop->getName();
                $phpDoc = PhpDocParser::parse($prop->getDocComment());
                $jsType = null;
                $phpType = null;
                foreach ($phpDoc['annotations'] as $row) {
                    if ($row['type'] === 'jstype') {
                        $jsType = implode("", $row['value']);
                    } elseif ($row['type'] === 'var') {
                        $phpType = implode("", $row['value']);
                        $phpType = str_replace(["int", "float"], "number", $phpType);
                    }
                }
                $jsData .= " * @property {" . ($jsType ?? $phpType ?? '') . "} $propName " . implode(" ",
                        $phpDoc['description']) . "\n";
            }
            $jsData .= "*/";
            file_put_contents($jsFilePath, $jsData);
        }
    }

    public static function getJsTypeName(): string
    {
        $class = str_replace("\\Framelix\\", "\\", static::class);
        return str_replace("\\", "", $class);
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}