<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View\Backend\Setup;

use function call_user_func_array;
use function class_exists;
use function error_reporting;
use function explode;
use function file_exists;
use function implode;
use function ini_set;
use function is_dir;
use function mb_internal_encoding;
use function ob_get_level;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function spl_autoload_register;
use function str_replace;
use function str_starts_with;
use function substr;

use const E_ALL;
use const FRAMELIX_MODULE;

/**
 * Framelix - The beginning
 */
class Framelix
{
    /**
     * The version of the framelix core itself
     * Will be replaced in production build with actual version number
     */
    public const VERSION = "dev";

    /**
     * @var string[]
     */
    public static array $registeredModules = [];


    /**
     * Initializes the framework
     * @codeCoverageIgnore
     */
    public static function init(): void
    {
        // report all errors, everything, we not accept any error
        error_reporting(E_ALL);
        ini_set("display_errors", '1');
        // everything is utf8
        ini_set("default_charset", "utf-8");
        mb_internal_encoding("UTF-8");
        // disable zlib, should be handled by webserver
        ini_set("zlib.output_compression", '0');

        require_once __DIR__ . "/Utils/Buffer.php";
        require_once __DIR__ . "/Utils/FileUtils.php";
        Buffer::$startBufferIndex = ob_get_level();

        // autoloader for all framework classes
        spl_autoload_register(function (string $className): void {
            if (!str_starts_with($className, "Framelix\\")) {
                return;
            }
            $exp = explode("\\", $className);
            $module = $exp[1];
            // ignore modules that are not yet registered
            if (
                $module !== "Framelix"
                && $module !== FRAMELIX_MODULE
                && !isset(self::$registeredModules[$module])
            ) {
                return;
            }
            unset($exp[0], $exp[1]);
            $rootPath = FileUtils::getModuleRootPath($module);
            // for src classes
            $path = $rootPath . "/src/" . implode("/", $exp) . ".php";
            if (file_exists($path)) {
                require $path;
            }
        });

        // exception handling
        set_error_handler([ErrorHandler::class, "onError"], E_ALL);
        set_exception_handler([ErrorHandler::class, "onException"]);
        register_shutdown_function([__CLASS__, "onShutdown"]);

        // grab dev mode initially from env
        Config::$devMode = !!($_SERVER['FRAMELIX_DEVMODE'] ?? null);

        self::registerModule("Framelix");

        // CLI have more time and memory by default
        Config::setTimeAndMemoryLimit(self::isCli() ? 4 : 1, self::isCli() ? 1024 : 128);

        // setup required, skip everything and init with minimal data
        if (!Config::doesUserConfigFileExist()) {
            View::$availableViews = [];
            if (self::isCli()) {
                return;
            }
            View::addAvailableView(Setup::class, false);
            return;
        } elseif (Config::$salts['default'] === 'none') {
            throw new FatalError('You have to set a proper random default salt with Config::addSalt()');
        }
        $lang = Lang::getLanguageByBrowserSettings();
        // in case user browser lang is available, use this as default
        if ($lang) {
            Config::$language = $lang;
        }
    }

    /**
     * On shutdown
     * Called when system is shutting down, the real last script to run
     * Checks for errors and display them
     * @codeCoverageIgnore
     */
    public static function onShutdown(): void
    {
        if ($error = error_get_last()) {
            if (in_array(
                $error["type"],
                [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT]
            )) {
                ErrorHandler::onException(new FatalError($error["message"]));
            }
        }
    }

    /**
     * Register a module by its folder name
     * @param string $module
     */
    public static function registerModule(string $module): void
    {
        if (isset(self::$registeredModules[$module])) {
            return;
        }
        $moduleDir = __DIR__ . "/../../" . $module;
        if (!is_dir($moduleDir)) {
            throw new FatalError($module . " not exist");
        }

        // composer autoloader
        $composerFile = $moduleDir . "/vendor/autoload.php";
        if (file_exists($composerFile)) {
            require $composerFile;
        }

        self::$registeredModules[$module] = $module;
        $configClass = "\\Framelix\\" . $module . "\\Config";
        if (class_exists($configClass)) {
            call_user_func_array([$configClass, "onRegister"], []);
        }
        $require = function ($file) {
            require $file;
        };
        // include user config files
        $files = FileUtils::getFiles(FileUtils::getUserdataFilepath("config", false, $module), "~\.php$~");
        foreach ($files as $file) {
            $require($file);
        }
        Lang::addValuesForModule($module);
        View::updateMetadata($module);
        View::addAvailableViewsByModule($module);
        Compiler::compile($module);
    }

    /**
     * Is app running in command line mode
     * @return bool
     */
    public static function isCli(): bool
    {
        return php_sapi_name() === "cli";
    }

    /**
     * Add ps4 autoloader
     * Usefull when scripts are not installed via composer
     * @param string $namespace
     * @param string $folder
     * @codeCoverageIgnore
     */
    public static function addPs4Autoloader(string $namespace, string $folder): void
    {
        spl_autoload_register(function ($class) use ($namespace, $folder) {
            $destinations = [
                $namespace => $folder
            ];
            foreach ($destinations as $prefix => $base_dir) {
                // does the class use the namespace prefix?
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) !== 0) {
                    // no, move to the next registered autoloader
                    continue;
                }

                // get the relative class name
                $relative_class = substr($class, $len);

                // replace the namespace prefix with the base directory, replace namespace
                // separators with directory separators in the relative class name, append
                // with .php
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

                // if the file exists, require it
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });
    }

}