<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\Shell;
use JetBrains\PhpStorm\ExpectedValues;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_unshift;
use function array_values;
use function basename;
use function count;
use function date;
use function file_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function ltrim;
use function mkdir;
use function readline;
use function readline_add_history;
use function str_starts_with;

use const FRAMELIX_MODULE;
use const FRAMELIX_USERDATA_FOLDER;

/**
 * Console runner
 * As this does do very complicated tasks ignore coverage for now
 * @codeCoverageIgnore
 */
class Console
{
    public const CONSOLE_SCRIPT = __DIR__ . "/../console.php";

    /**
     * Do not output anything
     * @var bool
     */
    static bool $quiet = false;

    /**
     * Overriden parameters
     * @var array
     */
    protected static array $overridenParameters = [];

    /**
     * Test if app is healthy
     * @return int Status Code, 0 = success
     */
    public static function healthCheck(): int
    {
        User::getByConditionOne();
        return 0;
    }

    /**
     * Backup app each individual sqlite database that are added to the config to /framelix/userdata/backups
     * @param string|null $filenamePrefix Backup filename
     * @return int Status Code, 0 = success
     */
    public static function backupSqliteDatabases(?string $filenamePrefix = null): int
    {
        $backupFolder = FRAMELIX_USERDATA_FOLDER . "/backups";
        if (!file_exists($backupFolder)) {
            mkdir($backupFolder, recursive: true);
        }
        foreach (Config::$sqlConnections as $key => $row) {
            if ($row['type'] === Sql::TYPE_SQLITE) {
                $filename = $filenamePrefix . basename($row['path']) .
                    "_" . $key . "_" . date("Y-m-d-H-i-s") . ".db";
                copy($row['path'], $backupFolder . "/" . $filename);
            }
        }
        return 0;
    }

    /**
     * Backup the complete mysql database from the container to /framelix/userdata/backups
     * @param string|null $filename Backup filename
     * @return int Status Code, 0 = success
     */
    public static function backupMysqlDatabase(?string $filename = null): int
    {
        $filename = $filename ?? date("Y-m-d-H-i-s") . ".sql";
        $shell = Shell::prepare('framelix_backup_mariadb {*}', [$filename]);
        $shell->execute();
        if ($shell->status > 0) {
            self::error($shell->getOutput());
        } else {
            self::line($shell->getOutput());
        }
        return $shell->status;
    }

    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        // if a default db connection is added, create and update database automatically on app startup
        $config = Config::$sqlConnections[FRAMELIX_MODULE] ?? null;
        if ($config) {
            // create database when using containers mariadb database service
            if ($config['type'] === Sql::TYPE_MYSQL && ($config['host'] === "127.0.0.1" || $config['host'] === "localhost")) {
                Shell::prepare("mysql -u root -papp -e 'CREATE DATABASE IF NOT EXISTS `" . FRAMELIX_MODULE . "`'")
                    ->execute();
            }
            $db = Sql::get();
            $builder = new SqlStorableSchemeBuilder($db);
            $queries = $builder->getSafeQueries();
            if ($queries) {
                self::info("Database upgrade");
                $builder->executeQueries($queries);
                self::success(count($queries) . " safe queries has been executed");
            } else {
                self::success("Everything was already up 2 date");
            }
        }
        return 0;
    }

    /**
     * Update database (Only safe queries)
     * @return int Status Code, 0 = success
     */
    public static function updateDatabaseSafe(): int
    {
        $builder = new SqlStorableSchemeBuilder(Sql::get());
        $queries = $builder->getSafeQueries();
        $builder->executeQueries($queries);
        self::success(count($queries) . " safe queries has been executed");
        return 0;
    }

    /**
     * Update database (Only unsafe queries)
     * @return int Status Code, 0 = success
     */
    public static function updateDatabaseUnsafe(): int
    {
        $builder = new SqlStorableSchemeBuilder(Sql::get());
        $queries = $builder->getUnsafeQueries();
        $builder->executeQueries($queries);
        self::success(count($queries) . " unsafe queries has been executed");
        return 0;
    }

    /**
     * The cron tasks, runs automatically in 5 minutes intervall
     * @return int Status Code, 0 = success
     */
    public static function cron(): int
    {
        $exitCode = 0;
        foreach (Framelix::$registeredModules as $module) {
            $cronClass = "\\Framelix\\$module\\Cron";
            if (class_exists($cronClass) && method_exists($cronClass, "runCron")) {
                try {
                    $start = microtime(true);
                    call_user_func_array([$cronClass, "runCron"], []);
                    $diff = microtime(true) - $start;
                    $diff = round($diff * 1000);
                    $info = "[OK] Job $cronClass::run() done in {$diff}ms";
                    self::success("$info");
                } catch (Throwable $e) {
                    $info = "[ERR] Job $cronClass::run() error: " . $e->getMessage();
                    self::error("$info");
                    $exitCode = 1;
                }
            } else {
                self::warn("[SKIP] $module as no cron handler is installed");
            }
        }
        return $exitCode;
    }

    /**
     * Call console script via php command line interpreter in a separate process
     * @param string $methodName
     * @param array|null $parameters
     * @return Shell
     */
    public static function callMethodInSeparateProcess(string $methodName, ?array $parameters = null): Shell
    {
        if (!is_array($parameters)) {
            $parameters = [];
        }
        array_unshift($parameters, $methodName);
        array_unshift($parameters, self::CONSOLE_SCRIPT);
        $shell = Shell::prepare("php {*}", $parameters);
        $shell->execute();
        return $shell;
    }

    /**
     * Draw error text in red
     * @param string $text
     * @return void
     */
    public static function error(string $text): void
    {
        if (self::$quiet) {
            return;
        }
        echo "\e[31m$text\e[0m\n";
    }

    /**
     * Draw a warn text
     * @param string $text
     * @return void
     */
    public static function warn(string $text): void
    {
        if (self::$quiet) {
            return;
        }
        echo "\e[93m$text\e[0m\n";
    }

    /**
     * Draw a success text
     * @param string $text
     * @return void
     */
    public static function success(string $text): void
    {
        if (self::$quiet) {
            return;
        }
        echo "\e[32m$text\e[0m\n";
    }

    /**
     * Draw a blue info line
     * @param string $text
     * @return void
     */
    public static function info(string $text): void
    {
        if (self::$quiet) {
            return;
        }
        echo "\e[34m$text\e[0m\n";
    }

    /**
     * Draw a line with given text
     * @param string $text
     * @return void
     */
    public static function line(string $text): void
    {
        if (self::$quiet) {
            return;
        }
        echo "$text\n";
    }

    /**
     * Display a message after which the user must enter some text
     * The entered text is returned
     * @param string $message
     * @param string[]|null $availableAnswers Only this answer are accepted, the question will be repeated until the user enter a correct answer
     * @param string|null $defaultAnswer
     * @return string
     */
    public static function question(
        string $message,
        ?array $availableAnswers = null,
        ?string $defaultAnswer = null
    ): string {
        $readlinePrompt = $message;
        if (is_array($availableAnswers)) {
            $readlinePrompt .= " [" . implode("|", $availableAnswers) . "]";
        }
        if (is_string($defaultAnswer)) {
            $readlinePrompt .= " (Default: $defaultAnswer)";
        }
        $readlinePrompt .= ": ";
        $answer = readline($readlinePrompt);
        if (is_array($availableAnswers) && !in_array($answer, $availableAnswers)) {
            return self::question($message, $availableAnswers, $defaultAnswer);
        }
        readline_add_history($answer);
        return is_string($answer) ? $answer : '';
    }

    /**
     * Override a given command line parameter (Used when invoking a method at the inside of another method)
     * @param string $name
     * @param array|null $value
     * @return void
     */
    public static function overrideParameter(string $name, ?array $value): void
    {
        self::$overridenParameters[$name] = $value;
    }

    /**
     * Get a single command line parameter
     * Example: --foo bar => 'bar'
     * Example: --foo => true
     * @param string $name
     * @param string|null $requiredParameterType If set, then parameter must be this type, can be: string|bool
     * @return string|bool|null
     */
    public static function getParameter(
        string $name,
        #[ExpectedValues(values: ["string", "bool"])]
        ?string $requiredParameterType = null
    ): string|bool|null {
        $arr = self::getParameters($name);
        if (!$arr) {
            if ($requiredParameterType) {
                self::error("Missing required parameter '--$name'");
                throw new SoftError();
            }
            return null;
        }
        $param = $arr[0];
        if ($requiredParameterType === 'string' && !is_string($param)) {
            self::error("Parameter '--$name' needs to be a string instead of boolean flag");
            throw new SoftError();
        }
        if ($requiredParameterType === 'bool' && !is_bool($param)) {
            self::error("Parameter '--$name' needs to be a bool flag instead of string value");
            throw new SoftError();
        }
        return $param;
    }

    /**
     * Get multiple command line parameters with the same name
     * Example: --foo bar --foo zar --foo => ['bar', 'zar', true]
     * @param string $name
     * @return string[]|bool[]
     */
    public static function getParameters(string $name): array
    {
        if (array_key_exists($name, self::$overridenParameters)) {
            return self::$overridenParameters[$name];
        }
        $args = $_SERVER['argv'] ?? [];
        if (!$args) {
            return [];
        }
        array_shift($args);
        array_shift($args);
        $arr = [];
        $validArg = false;
        foreach ($args as $arg) {
            if ($arg === "--" . $name) {
                $validArg = true;
                $arr[$name] = true;
            } elseif ($validArg && !str_starts_with($arg, "--")) {
                $arr[$name] = $arg;
                break;
            }
        }
        return array_values($arr);
    }
}