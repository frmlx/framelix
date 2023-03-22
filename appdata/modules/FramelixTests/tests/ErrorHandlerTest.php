<?php

use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

final class ErrorHandlerTest extends TestCase
{
    public function tests(): void
    {
        Config::$devMode = false;
        // extended error log test
        $testException = null;
        try {
            Config::$errorLogExtended = true;
            throw new FatalError("test");
        } catch (Throwable $e) {
            $testException = $e;
            $this->assertNotEquals(
                'Available with extended log only',
                ErrorHandler::throwableToJson($testException)['additionalData']['server']
            );
        }
        // normal error log test
        $e = null;
        try {
            Config::$errorLogExtended = false;
            throw new FatalError("test");
        } catch (Throwable $e) {
            $this->assertEquals(
                'Available with extended log only',
                ErrorHandler::throwableToJson($testException)['additionalData']['server']
            );
        }
        // testing php error handler
        $e = null;
        try {
            ErrorHandler::onError(E_ERROR, "Test", __FILE__, 20);
        } catch (Throwable $e) {
        }
        $this->assertInstanceOf(Exception::class, $e);
        // testing php error handler with @ suppression
        $e = null;
        $oldReporting = error_reporting();
        try {
            error_reporting(E_ALL & ~E_ERROR);
            ErrorHandler::onError(E_ERROR, "Test", __FILE__, 20);
        } catch (Throwable $e) {
        }
        $this->assertNull($e);
        error_reporting($oldReporting);

        // testing raw show exception log
        Buffer::start();
        ErrorHandler::showErrorFromExceptionLog(
            ErrorHandler::throwableToJson($testException),
            false
        );
        $this->assertTrue(str_contains(Buffer::get(), '<pre'));

        // testing log to disk
        $logDir = ErrorHandler::LOGFOLDER;
        $logFiles = FileUtils::getFiles($logDir);
        foreach ($logFiles as $logFile) {
            unlink($logFile);
        }
        ErrorHandler::saveErrorLogToDisk(ErrorHandler::throwableToJson($testException));
        $logFiles = FileUtils::getFiles($logDir);
        $this->assertCount(1, $logFiles);
        foreach ($logFiles as $logFile) {
            unlink($logFile);
        }
    }
}
