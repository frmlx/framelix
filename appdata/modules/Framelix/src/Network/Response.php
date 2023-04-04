<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;

use function basename;
use function call_user_func_array;
use function file_exists;
use function header;
use function headers_sent;
use function http_response_code;
use function readfile;
use function substr;

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
     * @param string|StorableFile $fileOrData If starting with @, the parameter will be threaded as string rather than file
     * @param string|null $filename
     * @param string|null $filetype
     * @param callable|null $afterDownload A hook after download before script execution stops
     * @return never
     */
    public static function download(
        string|StorableFile $fileOrData,
        ?string $filename = null,
        ?string $filetype = "application/octet-stream",
        ?callable $afterDownload = null
    ): never {
        if ($fileOrData instanceof StorableFile) {
            $filename = $filename ?? $fileOrData->filename;
            $isFile = true;
            $fileOrData = $fileOrData->getPath();
            if (!$fileOrData) {
                http_response_code(404);
                throw new SoftError();
            }
        } else {
            $isFile = !str_starts_with($fileOrData, "@");
            if (!$isFile) {
                $fileOrData = substr($fileOrData, 1);
            }
        }
        if ($isFile && !file_exists($fileOrData)) {
            http_response_code(404);
            throw new SoftError();
        }
        self::header('Content-Description: File Transfer');
        self::header('Content-Type: ' . $filetype);
        self::header(
            'Content-Disposition: attachment; filename="' . basename(
                $isFile && !$filename ? basename($fileOrData) : $filename ?? "download.txt"
            ) . '"'
        );
        self::header('Expires: 0');
        self::header('Cache-Control: must-revalidate');
        self::header('Pragma: public');
        if ($isFile) {
            readfile($fileOrData);
        } else {
            echo $fileOrData;
        }
        if ($afterDownload) {
            call_user_func_array($afterDownload, []);
        }
        throw new SoftError();
    }

    /**
     * Stop script execution and output a json response to use in frontend
     * If toast messages where issued, then the messages will be displayed as well
     * @param array|string|null $errorMessages If null, form validation is considered successfull
     * @return never
     */
    public static function stopWithFormValidationResponse(array|string|null $errorMessages = null): never
    {
        http_response_code(200);
        JsonUtils::output([
            'toastMessages' => Toast::getQueueMessages(true),
            'errorMessages' => $errorMessages,
            'buffer' => Buffer::getAll()
        ]);
        throw new SoftError();
    }
}