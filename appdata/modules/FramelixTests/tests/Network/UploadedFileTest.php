<?php

namespace Network;

use Framelix\Framelix\Network\UploadedFile;
use Framelix\FramelixTests\TestCase;

use const UPLOAD_ERR_CANT_WRITE;

final class UploadedFileTest extends TestCase
{

    public function tests(): void
    {
        $uploadedFiles = UploadedFile::createFromSubmitData("test.txt");
        $this->assertNull($uploadedFiles);

        $this->addSimulatedFile("test.txt", "foobar", false, 'test.txt', UPLOAD_ERR_CANT_WRITE);
        $uploadedFiles = UploadedFile::createFromSubmitData("test.txt");
        $this->assertNull($uploadedFiles);

        $this->addSimulatedFile("test.txt", "foobar", false);
        $uploadedFiles = UploadedFile::createFromSubmitData("test.txt");
        $this->assertCount(1, $uploadedFiles);
        $this->assertSame("foobar", $uploadedFiles[0]->getFileContents());
        $this->assertSame("txt", $uploadedFiles[0]->getExtension());

        $this->addSimulatedFile("test", "foobar", true);
        $uploadedFiles = UploadedFile::createFromSubmitData("test");
        $this->assertCount(2, $uploadedFiles);
        $this->assertSame("foobar", $uploadedFiles[0]->getFileContents());
        $this->assertSame("foobar", $uploadedFiles[1]->getFileContents());
        $this->assertNull($uploadedFiles[0]->getExtension());

        $this->removeSimulatedFile("test.txt");
        $this->removeSimulatedFile("test");
    }
}
