<?php

use Framelix\Framelix\Console;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Utils\PhpDocParser;

if (php_sapi_name() !== 'cli' || !isset($_SERVER['argv'][0])) {
    echo "This script can only be called from command-line. Bye.";
    exit(1);
}

$argv = $_SERVER['argv'];
unset($argv[0]);
$moduleName = $argv[1] ?? null;
if (!$moduleName) {
    echo "First parameter must be a module name or 'all' for all modules. Bye.";
    exit(1);
}
unset($argv[1]);

if ($moduleName === "all") {
    // get all currently active module entry points and call each individually
    $environments = json_decode(file_get_contents("/framelix/system/environment.json"), true);
    foreach ($environments['moduleAccessPoints'] as $module => $data) {
        $moduleEntryPoint = __DIR__ . "/../$module/public/index.php";
        if (!file_exists($moduleEntryPoint)) {
            continue;
        }
        $exitCode = 0;
        $cmd = "php -f " . escapeshellarg(__FILE__) . " " . escapeshellarg($module);
        foreach ($argv as $arg) {
            $cmd .= " " . escapeshellarg($arg);
        }
        passthru($cmd, $exitCode);
        if ($exitCode) {
            exit($exitCode);
        }
    }
    exit(0);
}

$moduleEntryPoint = __DIR__ . "/../$moduleName/public/index.php";

if (!file_exists($moduleEntryPoint)) {
    echo $moduleEntryPoint . " does not exist";
    exit(2);
}

ini_set("memory_limit", -1);
ini_set("max_execution_time", -1);

$actions = [];

try {
    require $moduleEntryPoint;
} catch (Throwable $e) {
    echo "ERROR during framelix initialization\n\n";
    echo $e->getMessage() . "\n\n" . $e->getTraceAsString();
    exit(2);
}

// fetch all available jobs for all registered modules
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
        $actions[$name]['name'] = $name;
        $actions[$name]['callables'][$consoleClass] = $consoleClass . "::$name";
    }
}


$argAction = $argv[2] ?? '__missing__arg__';
$selectedAction = $actions[$argAction] ?? null;
if (!$selectedAction) {
    Console::warn("Action '$argAction' not available in '$moduleName'. Skipped.");
    return;
}

foreach ($argv as $arg) {
    if ($arg === "-q") {
        Console::$quiet = true;
    }
}

$jobTitle = "JOB $moduleName->" . $selectedAction['name'];
Console::info($jobTitle . " started");

$start = microtime(true);
$exitCode = 999;
foreach ($selectedAction['callables'] as $callable) {
    try {
        $exitCode = call_user_func_array($callable, []);
    } catch (Throwable $e) {
        Console::$quiet = false;
        Console::error('EXCEPTION');
        Console::line($e->getMessage() . "\n" . $e->getTraceAsString());
        $exitCode = 998;
    }
    if ($exitCode > 0) {
        break;
    }
}

if (!$exitCode) {
    Console::success($jobTitle . " finished");
    echo "\n";
} else {
    Console::error($jobTitle . ' failed with error code ' . $exitCode);
    echo "\n";
}

exit($exitCode);