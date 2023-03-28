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
use function file_get_contents;
use function implode;
use function ini_set;
use function is_dir;
use function mb_internal_encoding;
use function ob_get_level;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function set_time_limit;
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
     * @var string[]
     */
    public static array $registeredModules = [];

    /**
     * The version of the framelix module itself
     * @var string
     */
    public static string $version;

    /**
     * Initializes the framework
     * @codeCoverageIgnore
     */
    public static function init(): void
    {
        // report all errors, everything, we not accept any error
        error_reporting(E_ALL);
        ini_set("display_errors", '1');
        // default 60 seconds run time and 128M memory, suitable for most default app calls
        set_time_limit(60);
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

        // composer autoloader
        require __DIR__ . "/../vendor/autoload.php";

        // exception handling
        set_error_handler([ErrorHandler::class, "onError"], E_ALL);
        set_exception_handler([ErrorHandler::class, "onException"]);
        register_shutdown_function([__CLASS__, "onShutdown"]);

        self::$version = file_get_contents("/framelix/system/VERSION");

        // grab dev mode initially from env
        Config::$devMode = !!($_SERVER['FRAMELIX_DEVMODE'] ?? null);

        self::registerModule("Framelix");

        if (!self::isCli()) {
            // set memory limit to 128M as it is enough for almost every use case
            // increase it where it is required
            ini_set("memory_limit", "128M");
        }

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

    /**
     * Create minimal initial user config files (core and ui) to be able to use the application
     * Used in setup via web interface as well
     * Will throw an error if user config files aready exist
     * @param string $module
     * @param string $defaultSalt
     * @param string $applicationHost
     * @param string $applicationUrlPrefix
     */
    public static function createInitialUserConfig(
        string $module,
        string $defaultSalt,
        string $applicationHost,
        string $applicationUrlPrefix
    ): void {
        $userConfigFileCore = Config::getUserConfigFilePath(module: $module);
        $userConfigFileUi = Config::getUserConfigFilePath("02-ui", $module);
        if (file_exists($userConfigFileCore) || file_exists($userConfigFileUi)) {
            throw new FatalError("User config already exists");
        }
        $fileContents = [
            "<?php",
            "// this file contains all core settings that are not changable with UI or in the backend",
            "// this file can only be modified manually directly in this file",
            "\\Framelix\\Framelix\\Config::addSalt('" . $defaultSalt . "');",
            "\\Framelix\\Framelix\\Config::\$applicationHost = '" . $applicationHost . "';",
            "\\Framelix\\Framelix\\Config::\$applicationUrlPrefix = '" . $applicationUrlPrefix . "';"
        ];
        file_put_contents($userConfigFileCore, implode("\n", $fileContents));
        $fileContents = [
            "<?php",
            "// this file will be modified with changes in backend UI for system config",
            "// you should not manually update this, as it will be overriden when UI settings have changed",
            "// initially this is empty on purpose"
        ];
        file_put_contents($userConfigFileUi, implode("\n", $fileContents));
    }
}