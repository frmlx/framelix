<?php

namespace Network;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use function file_get_contents;
use function http_response_code;
use function ob_get_level;

final class ResponseTest extends TestCase
{

    public function tests(): void
    {
        Buffer::start();
        $this->assertExceptionOnCall(function () {
            Response::download(__FILE__, "foo", null, function () {});
        }, [], StopExecution::class);
        $this->assertSame(file_get_contents(__FILE__), Buffer::get());

        $testFilePath = __DIR__ . "/../../test-files/imageutils/test-image.jpg";

        // not exist test
        Buffer::start();
        $this->assertExceptionOnCall(function () use ($testFilePath) {
            Response::download($testFilePath);
        }, [], StopExecution::class);
        $this->assertSame(file_get_contents($testFilePath), Buffer::get());

        // not exist test
        $this->assertExceptionOnCall(function () {
            Response::download(__FILE__ . "NotExist");
        }, [], StopExecution::class);

        // not exist test
        $this->assertExceptionOnCall(function () {
            Response::download("blub");
        }, [], StopExecution::class);

        // test form validation response
        http_response_code(200);
        $oldIndex = Buffer::$startBufferIndex;
        Buffer::start();
        Buffer::$startBufferIndex = ob_get_level();
        $this->assertExceptionOnCall(function () {
            Response::stopWithFormValidationResponse(['test' => 'Error']);
        }, [], StopExecution::class);
        Buffer::$startBufferIndex = $oldIndex;
        $this->assertSame(200, http_response_code());
        $this->assertSame('{"toastMessages":[],"errorMessages":{"test":"Error"},"content":""}', Buffer::get());
        FileUtils::deleteDirectory(FileUtils::getUserdataFilepath("storablefile", true));
    }

}
