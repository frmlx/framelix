<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\SchemeBuilderRequirementsInterface;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Utils\Shell;
use JetBrains\PhpStorm\ExpectedValues;
use Throwable;

use function array_key_exists;
use function array_shift;
use function array_unshift;
use function array_values;
use function count;
use function date;
use function file_exists;
use function filesize;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function mkdir;
use function readline;
use function readline_add_history;
use function str_pad;
use function str_replace;
use function str_starts_with;
use function strlen;
use function unlink;

use const FRAMELIX_MODULE;
use const FRAMELIX_USERDATA_FOLDER;
use const STR_PAD_LEFT;

/**
 * Console runner
 * As this does do very complicated tasks ignore coverage for now
 * @codeCoverageIgnore
 */
class Console
{
    public const CONSOLE_SCRIPT = __DIR__ . "/../console.php";

    /**
     * Do not output anything when using ::error, ::warn, ::success, ::line
     * @var bool
     */
    public static bool $quiet = false;

    /**
     * Include timestamp when outputing a line with ::error, ::warn, ::success, ::info, ::line
     * @var bool
     */
    public static bool $includeTimestamp = true;

    /**
     * Include a line code (S,E,W,i) when outputing a line with ::error, ::warn, ::success, ::info
     * @var bool
     */
    public static bool $includeLineCode = true;

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
        // default is OK
        return 0;
    }

    /**
     * Called when the application is warmup, during every docker container start
     * Override this function to provide your own update/upgrade path
     * @return int Status Code, 0 = success
     */
    public static function appWarmup(): int
    {
        // if a default db connection is added, update database scheme automatically on app startup
        $config = Config::$sqlConnections[FRAMELIX_MODULE] ?? null;
        if ($config) {
            $db = Sql::get();
            $builder = new SqlStorableSchemeBuilder($db);
            $queries = $builder->getSafeQueries();
            if ($queries) {
                self::info("Database upgrade for '" . $db->id . "'");
                $builder->executeQueries($queries);
                self::success(count($queries) . " safe queries has been executed");
            }
            self::success("Database is '" . $db->id . "' Up2Date");
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
                    call_user_func_array([$cronClass, "runCron"], []);
                    $info = "$cronClass::run() finished";
                    self::success($info);
                } catch (Throwable $e) {
                    $info = "$cronClass::run() error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
                    self::error($info);
                    $exitCode = 1;
                }
            } else {
                self::warn("$cronClass::run() not exist. Skipped.");
            }
        }
        // truncate log to 2MB max. size of last logs
        $logFile = ErrorHandler::LOGFOLDER . '/framelix-cron.log';
        if (file_exists($logFile) && filesize($logFile) > 1024 * 1024 * 4) {
            $logFileCopy = $logFile . ".rotate";
            if (file_exists($logFileCopy)) {
                unlink($logFileCopy);
            }
            Shell::prepare(
                "tac $logFile > $logFileCopy && dd if=/dev/null of=$logFileCopy seek=1 bs=2M && tac $logFileCopy > $logFile"
            )->execute();
            if (file_exists($logFileCopy)) {
                unlink($logFileCopy);
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
        self::line($text, "31", "[E] ");
    }

    /**
     * Draw a warn text
     * @param string $text
     * @return void
     */
    public static function warn(string $text): void
    {
        self::line($text, "93", "[W] ");
    }

    /**
     * Draw a success text
     * @param string $text
     * @return void
     */
    public static function success(string $text): void
    {
        self::line($text, "32", "[S] ");
    }

    /**
     * Draw a blue info line
     * @param string $text
     * @return void
     */
    public static function info(string $text): void
    {
        self::line($text, "34", "[i] ");
    }

    /**
     * Draw a line with given text
     * @param string $text
     * @param string|null $colorCode Numeric bash color code for the message
     * @param string|null $lineCode Some string to prepend before the message
     * @return void
     */
    public static function line(string $text, ?string $colorCode = null, ?string $lineCode = null): void
    {
        if (self::$quiet) {
            return;
        }
        if ($lineCode && self::$includeLineCode) {
            // offset lines by line code length for indention
            $text = $lineCode . str_replace("\n", "\n" . strlen($lineCode), $text);
        }
        if ($colorCode) {
            $text = "\e[{$colorCode}m$text\e[0m";
        }
        if (self::$includeTimestamp) {
            $whole = microtime(true);
            $ms = str_pad((string)floor(($whole - floor($whole)) * 1000), 3, "0", STR_PAD_LEFT);
            $text = date("c") . ":$ms " . $text;
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
                throw new StopExecution();
            }
            return null;
        }
        $param = $arr[0];
        if ($requiredParameterType === 'string' && !is_string($param)) {
            self::error("Parameter '--$name' needs to be a string instead of boolean flag");
            throw new StopExecution();
        }
        if ($requiredParameterType === 'bool' && !is_bool($param)) {
            self::error("Parameter '--$name' needs to be a bool flag instead of string value");
            throw new StopExecution();
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