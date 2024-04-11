<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;

use function basename;
use function file_exists;
use function header;
use function headers_sent;
use function http_response_code;
use function readfile;

/**
 * Response utilities for frequent tasks
 */
class Response
{

    /**
     * Send http header, but only if it is possible (no headers are sent)
     * @param string $header
     * @return void
     */
    public static function header(string $header): void
    {
        // @codeCoverageIgnoreStart
        if (headers_sent()) {
            return;
        }
        // @codeCoverageIgnoreEnd
        header($header);
    }

    /**
     * Initialize a file download for the browser
     * @param string|StorableFile|callable $fileOrData A string is considered a file
     * @param string|null $filename
     * @param string|null $filetype
     * @param callable|null $afterDownload A hook after download before script execution stops
     * @return never
     */
    public static function download(
        string|StorableFile|callable $fileOrData,
        ?string $filename = null,
        ?string $filetype = "application/octet-stream",
        ?callable $afterDownload = null
    ): never {
        if ($fileOrData instanceof StorableFile) {
            $filename = $filename ?? $fileOrData->filename;
            $fileOrData = $fileOrData->getPath();
        }
        if (is_string($fileOrData)) {
            if (!file_exists($fileOrData)) {
                http_response_code(404);
                throw new StopExecution();
            }
            if (!$filename) {
                $filename = basename($fileOrData);
            }
        }
        self::header('Content-Description: File Transfer');
        self::header('Content-Type: ' . $filetype);
        self::header(
            'Content-Disposition: attachment; filename="' . ($filename ?? "download.txt") . '"'
        );
        self::header('Expires: 0');
        self::header('Pragma: public');
        if (is_string($fileOrData)) {
            self::header('Cache-Control: no-store');
            readfile($fileOrData);
        } elseif (is_callable($fileOrData)) {
            call_user_func($fileOrData);
        }
        if ($afterDownload) {
            call_user_func($afterDownload);
        }
        throw new StopExecution();
    }

    /**
     * Stop script execution and output a json response to use in frontend
     * If toast messages where issued, then the messages will be displayed as well
     * @param array|string|null $errorMessages If null, form validation is considered successfull
     * @return never
     */
    public static function stopWithFormValidationResponse(array|string|null $errorMessages = null): never
    {
        JsonUtils::output([
            'toastMessages' => Toast::getQueueMessages(true),
            'errorMessages' => $errorMessages,
            'content' => Buffer::getAll(),
        ]);
        throw new StopExecution();
    }

}