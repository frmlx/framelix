<?php

namespace Network;

use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\FramelixTests\Storable\TestStorableFile;
use Framelix\FramelixTests\TestCase;

use function file_get_contents;
use function file_put_contents;
use function unlink;

final class ResponseTest extends TestCase
{

    public function tests(): void
    {
        $storableFile = new TestStorableFile();
        FileUtils::deleteDirectory("/framelix/userdata/" . $storableFile->relativePathOnDisk);
        mkdir("/framelix/userdata/" . $storableFile->relativePathOnDisk);

        Buffer::start();
        try {
            Response::download("@filecontent", "foo");
        } catch (SoftError) {
            $this->assertSame("filecontent", Buffer::get());
        }
        Buffer::start();
        try {
            Response::download(__FILE__, "foo", null, function () {
            });
        } catch (SoftError) {
            $this->assertSame(file_get_contents(__FILE__), Buffer::get());
        }
        $file = new TestStorableFile();
        $file->relativePathOnDisk = "test.txt";
        $filePath = $file->getPath(false);
        Buffer::start();
        try {
            file_put_contents($filePath, "foobar");
            Response::download($file);
        } catch (SoftError) {
            $this->assertSame("foobar", Buffer::get());
        }
        unlink($file->getPath(true));

        // not exist test
        Buffer::start();
        try {
            Response::download(__FILE__ . "NotExist");
        } catch (SoftError) {
            $this->assertSame("", Buffer::get());
        }

        // not exist test
        Buffer::start();
        try {
            Response::download($file);
        } catch (SoftError) {
            $this->assertSame("", Buffer::get());
        }

        Buffer::start();
        try {
            Response::stopWithFormValidationResponse();
        } catch (SoftError) {
            $this->assertSame(200, http_response_code());
            $this->assertTrue(true);
            $this->assertSame('{"modalMessage":null,"reloadTab":false,"toastMessages":[]}', Buffer::get());
        }

        Buffer::start();
        try {
            Response::stopWithFormValidationResponse('foobar');
        } catch (SoftError) {
            $this->assertSame(JsonUtils::encode("foobar"), Buffer::get());
        }
    }
}
