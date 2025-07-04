<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Exception\Redirect;
use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\Email;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Throwable;

use function clearstatcache;
use function explode;
use function file_put_contents;
use function htmlentities;
use function http_response_code;
use function implode;
use function json_encode;
use function ksort;
use function php_sapi_name;
use function time;

use const FRAMELIX_MODULE;
use const JSON_PRETTY_PRINT;

/**
 * Framelix exception and error handling
 */
class ErrorHandler
{

    public const string LOGFOLDER = FRAMELIX_USERDATA_FOLDER . "/" . FRAMELIX_MODULE . "/_logs";

    /**
     * Throwable to json
     * @param Throwable $e
     * @return array
     */
    public static function throwableToJson(Throwable $e): array
    {
        if (Config::$errorLogExtended || Config::$devMode) {
            $server = $_SERVER;
            ksort($server);
            $post = $_POST;
            ksort($post);
            $session = $_SESSION ?? [];
            ksort($session);
            $cookie = $_COOKIE;
            ksort($cookie);
        } else {
            $server = 'Available with extended log only';
            $post = 'Available with extended log only';
            $session = 'Available with extended log only';
            $cookie = 'Available with extended log only';
        }
        return [
            'time' => time(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'traceSimple' => explode("\n", $e->getTraceAsString()),
            'traceExtended' => $e->getTrace(),
            'additionalData' => [
                'server' => $server,
                'post' => $post,
                'session' => $session,
                'cookie' => $cookie,
            ],
        ];
    }

    /**
     * Show error as html from exception data
     * @param array $logData
     * @param bool $forceShowDetails If true, then all exception data will be shown. If false, this info is only
     *     visible in devMode
     */
    public static function showErrorFromExceptionLog(array $logData, bool $forceShowDetails = false): void
    {
        if (!Config::$devMode && !$forceShowDetails) {
            echo '<pre style="color:red; font-weight: bold">' . htmlentities($logData['message']) . '</pre>';
        } else {
            $id = RandomGenerator::getRandomHtmlId();
            $html = [
                'title' => htmlentities($logData['message']) . ' in ' . $logData['file'] . '(' . $logData['line'] . ')',
                'trace' => implode('</pre><pre class="framelix-error-log-trace">', $logData['traceSimple']),
            ];
            ?>
            <div id="<?= $id ?>" class="framelix-error-log">
                <small><?= DateTime::anyToFormat($logData['time'] ?? null, "d.m.Y H:i:s") ?></small>
                <pre class="framelix-error-log-title"><?= $html['title'] ?></pre>
                <pre class="framelix-error-log-trace"><?= $html['trace'] ?></pre>
                <pre class="framelix-error-log-json"><?= JsonUtils::encode(
                        $logData['additionalData'] ?? null,
                        true
                    ) ?></pre>
            </div>
            <style>
              .framelix-error-log {
                padding: 10px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.3);
              }

              .framelix-error-log-title {
                color: var(--color-error-text, red);
                font-weight: bold;
                max-width: 100%;
                overflow-x: auto;
                white-space: pre-line;
                margin: 0;
                font-size: 0.9rem;
              }

              .framelix-error-log-trace,
              .framelix-error-log-json {
                max-width: 100%;
                overflow-x: auto;
                white-space: pre-wrap;
                text-indent: -27px;
                padding-left: 27px;
                display: block;
                margin: 0;
                font-size: 0.8rem;
              }
            </style>
            <script>
              (function () {
                const errorData = <?=json_encode($logData)?>;
                console.log('Framelix Error', errorData)
              })()
            </script>
            <?php
        }
    }

    /**
     * Save error log to disk
     * @param array $logData
     */
    public static function saveErrorLogToDisk(array $logData): void
    {
        $path = self::LOGFOLDER . "/error-" . time() . "-" . RandomGenerator::getRandomString(3, 6) . ".json";
        if (!is_dir(self::LOGFOLDER)) {
            mkdir(self::LOGFOLDER, recursive: true);
            clearstatcache();
        }
        file_put_contents($path, json_encode($logData, JSON_PRETTY_PRINT));
    }

    /**
     * Send error log email
     * @param array $logData
     * @codeCoverageIgnore
     */
    public static function sendErrorLogEmail(array $logData): void
    {
        $email = Config::$errorLogEmail;
        if ($email && Email::isAvailable() && !Config::$devMode) {
            $body = '<h2 style="color:red">' . htmlentities($logData['message']) . '</h2>';
            $body .= '<pre>' . htmlentities(implode("\n", $logData['traceSimple'])) . '</pre>';
            $body .= '<pre>' . htmlentities(JsonUtils::encode($logData['additionalData'] ?? null, true)) . '</pre>';
            // do not use subject and body templates for errors mails
            $originalSubject = Config::$emailSubject;
            $originalBody = Config::$emailBody;
            Config::$emailSubject = '{subject}';
            Config::$emailBody = '{body}';
            Email::send(
                'ErrorLog: ' . $logData['message'],
                $body,
                $email
            );
            Config::$emailSubject = $originalSubject;
            Config::$emailBody = $originalBody;
        }
    }

    /**
     * On exception
     * Is called when an exception occurs
     * @param Throwable $e
     * @codeCoverageIgnore
     */
    public static function onException(Throwable $e): void
    {
        // a redirect exception
        if ($e instanceof Redirect) {
            // on async, we don't want to do an actual transparent redirect, we want the frontend to do it for us
            if (Request::isAsync()) {
                Response::header("x-redirect: " . $e->url);
            } else {
                if (!headers_sent()) {
                    http_response_code($e->getCode());
                }
                Response::header("location: " . $e->url);
            }
            return;
        }
        // a stop exception does nothing, it is a gracefull expected stop of script execution
        if ($e instanceof StopExecution) {
            return;
        }
        $buffer = Buffer::getAll();
        $logData = self::throwableToJson($e);
        $logData['buffer'] = $buffer;
        try {
            self::saveErrorLogToDisk($logData);
            self::sendErrorLogEmail($logData);
        } catch (Throwable $e) {
        }
        // on command line, output raw exception data and set error status code
        if (php_sapi_name() === 'cli') {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
            exit($e->getCode() > 0 ? $e->getCode() : 1);
        }
        // on server, return with error response code and let the views handles the output
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (!View::$activeView) {
            ErrorHandler::showErrorFromExceptionLog($logData);
        } else {
            try {
                View::$activeView->onException($logData);
            } catch (Throwable $subE) {
                if ($subE instanceof StopExecution) {
                    return;
                }
                Buffer::clear();
                echo '<h2 style="color: red">There is an error in the error handler</h2>';
                echo '<h3 style="color: red">Original Error</h3>';
                ErrorHandler::showErrorFromExceptionLog($logData);
                echo '<h3 style="color: red">Errow while handling original error</h3>';
                ErrorHandler::showErrorFromExceptionLog(self::throwableToJson($subE));
            }
        }
    }

    /**
     * On php error
     * Is called when a php error occurs
     * @param mixed $errno
     * @param mixed $errstr
     * @param mixed $errfile
     * @param mixed $errline
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public static function onError(mixed $errno, mixed $errstr, mixed $errfile, mixed $errline): bool
    {
        // check if error was suppressed with @
        // ugly but possible and done by some 3rd party libraries
        if (!(error_reporting() & $errno)) {
            return true;
        }
        throw new FatalError($errstr);
    }

}