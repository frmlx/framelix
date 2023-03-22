<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;
use JetBrains\PhpStorm\ExpectedValues;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_unshift;
use function array_values;
use function count;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function preg_match;
use function readline;
use function readline_add_history;
use function str_starts_with;
use function strtotime;
use function unlink;
use function version_compare;

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
     * Backup app database to /framelix/userdata/backups
     * @param string|null $filename Backup filename
     * @return int Status Code, 0 = success
     */
    public static function backupAppDatabase(?string $filename = null): int
    {
        $filename = $filename ?? date("Y-m-d-H-i-s") . ".sql";
        $shell = Shell::prepare('framelix_backup_db {*}', [$filename]);
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
        self::info("Database upgrade");
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $queries = $builder->getSafeQueries();
        if ($queries) {
            $builder->executeQueries($queries);
            self::success(count($queries) . " safe queries has been executed");
        } else {
            self::success("Everything was already up 2 date");
        }
        return 0;
    }

    /**
     * Update database (Only safe queries)
     * @return int Status Code, 0 = success
     */
    public static function updateDatabaseSafe(): int
    {
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
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
        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $queries = $builder->getUnsafeQueries();
        $builder->executeQueries($queries);
        self::success(count($queries) . " unsafe queries has been executed");
        return 0;
    }

    /**
     * Check for app updates
     * @return int Status Code, 0 = success
     */
    public static function checkAppUpdate(): int
    {
        if (file_exists(Framelix::VERSION_UPGRADE_FILE)) {
            unlink(Framelix::VERSION_UPGRADE_FILE);
        }
        $versionData = file_exists(Framelix::VERSION_FILE) ? JsonUtils::readFromFile(Framelix::VERSION_FILE) : null;
        if (($versionData['dockerRepo'] ?? null) && preg_match("~^[0-9]+\.[0-9]+\.[0-9]+$~", $versionData['tag'])) {
            $spl = explode("/", $versionData['dockerRepo']);
            $hubApiUrl = 'https://hub.docker.com/v2/namespaces/' . $spl[0] . '/repositories/' . $spl[1] . '/tags';
            $browser = Browser::create();
            $browser->url = $hubApiUrl;
            $browser->sendRequest();
            $apiData = $browser->getResponseJson()['results'] ?? null;
            if ($apiData) {
                $updatedVersion = null;
                foreach ($apiData as $row) {
                    if ($row['tag_status'] === 'active' && preg_match("~^[0-9]+\.[0-9]+\.[0-9]+$~", $row['name'])) {
                        if (version_compare($row['name'], $versionData['tag'], '>')) {
                            if (!$updatedVersion || strtotime($row['tag_last_pushed']) > strtotime(
                                    $updatedVersion['tag_last_pushed']
                                )) {
                                $updatedVersion = $row;
                            }
                        }
                    }
                }
                if ($updatedVersion) {
                    $updatedVersion['dockerRepo'] = $versionData['dockerRepo'];
                    JsonUtils::writeToFile(Framelix::VERSION_UPGRADE_FILE, $updatedVersion);
                }
            }
        }
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