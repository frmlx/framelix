<?php

use Framelix\Framelix\Console;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Utils\PhpDocParser;

if (php_sapi_name() !== 'cli' || !isset($_SERVER['argv'][0])) {
    echo "This script can only be called from command-line. Bye.";
    exit(1);
}

ini_set("memory_limit", -1);
ini_set("max_execution_time", -1);

$argv = $_SERVER['argv'];
unset($argv[0]);

$actions = [];

try {
    require __DIR__ . "/public/index.php";
} catch (Throwable $e) {
    echo "ERROR during framelix initialization\n\n";
    echo $e->getMessage() . "\n\n" . $e->getTraceAsString();
    exit(2);
}

// fetch all available jobs
foreach (Framelix::$registeredModules as $module) {
    $consoleClass = "\\Framelix\\$module\\Console";
    if (!class_exists($consoleClass)) {
        continue;
    }
    $reflectionClass = new ReflectionClass($consoleClass);
    foreach ($reflectionClass->getMethods() as $method) {
        if (!$method->isPublic() || !$method->isStatic()) {
            continue;
        }
        if ($method->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
            continue;
        }
        $returnType = $method->getReturnType();
        // only methods returning a status code are valid console commands
        if (!method_exists($returnType, 'getName') || $returnType->getName() !== 'int') {
            continue;
        }
        $name = $method->getName();
        $parsedComment = PhpDocParser::parse($method->getDocComment());
        $description = trim(implode("\n", $parsedComment['description']));
        if ($description) {
            if (!isset($actions[$name]['description'])) {
                $actions[$name]['description'] = '';
            }
            $actions[$name]['description'] .= $description . "\n";
        }
        $actions[$name]['callables'][$consoleClass] = $consoleClass . "::$name";
    }
}

$selectedAction = $actions[$argv[1] ?? -1] ?? null;
if (!$selectedAction) {
    Console::info("# =============================");
    Console::info("# FRAMELIX CONSOLE - 😜  Huhuu!");
    Console::info("# Call a script with framelix_console {actionName} [optional parameters]");
    Console::info("# Availabe action names are:");
    Console::info("# =============================");
    foreach ($actions as $action => $row) {
        $lines = explode("\n", trim($row['description'] ?? ''));
        $firstLine = array_shift($lines);
        foreach ($lines as $key => $line) {
            $lines[$key] = "   $line";
        }
        Console::line("*) " . $action . " => " . $firstLine);
        if ($lines) {
            Console::line(implode("\n", $lines));
        }
    }
    Console::line("");
    return;
}

$cron = false;
foreach ($argv as $arg) {
    if ($arg === "-q" || $arg === "-c") {
        Console::$quiet = true;
        if ($arg === "-c") {
            $cron = true;
        }
    }
}

if ($cron) {
    echo "Started at " . date("c");
}

$action = $actions[$argv[1]];
Console::info("# =============================");
Console::info("# FRAMELIX CONSOLE RUNNER");
Console::info("# COMMAND: " . $argv[1]);
Console::info("# CALLABLES: " . implode(", ", $action['callables']));
Console::info("# =============================");

$start = microtime(true);
$exitCode = 999;
foreach ($action['callables'] as $callable) {
    try {
        $exitCode = call_user_func_array($callable, []);
    } catch (Throwable $e) {
        Console::$quiet = false;
        Console::error('# EXCEPTION');
        Console::line($e->getMessage() . "\n" . $e->getTraceAsString());
        $exitCode = 998;
    }
    if ($exitCode > 0) {
        break;
    }
}
$diff = microtime(true) - $start;
$diff = round($diff * 1000);

if (!$exitCode) {
    if ($cron) {
        echo " | Finished at " . date("c") . " in  $diff ms\n";
    }
    Console::success('[SUCCESS] finished in ' . $diff . "ms");
} else {
    Console::error('[ERROR] (' . $exitCode . ') finished in ' . $diff . "ms");
}

exit($exitCode);