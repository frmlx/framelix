<?php

namespace Db\Storables;

use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\Storable\TestStorableFile;
use Framelix\FramelixTests\TestCaseDbTypes;

use function mkdir;

abstract class StorableFileTestBase extends TestCaseDbTypes
{
    public function test(): void
    {
        $storableFile = new TestStorableFile();
        FileUtils::deleteDirectory(FRAMELIX_USERDATA_FOLDER . "/" . $storableFile->relativePathOnDisk);
        mkdir(FRAMELIX_USERDATA_FOLDER . "/" . $storableFile->relativePathOnDisk, recursive: true);

        $this->setupDatabase();
        $this->addSimulatedFile("test.txt", "foobar", false);

        // simulate not existing file
        $this->assertExceptionOnCall(function () {
            $uploadedFile = UploadedFile::createFromSubmitData("test.txt")[0];
            $uploadedFile->path .= "1";
            $storableFile = new TestStorableFile();
            $storableFile->store(false, $uploadedFile);
        });

        // simulate missing file
        $this->assertExceptionOnCall(function () {
            $storableFile = new TestStorableFile();
            $storableFile->store();
        });

        // simulate missing filename
        $this->assertExceptionOnCall(function () {
            $storableFile = new TestStorableFile();
            $storableFile->store(false, "foobar");
        });

        $storableFile = new TestStorableFile();
        $storableFile->filename = "test.txt";
        $storableFile->store(false, "test");

        $storableFile2 = new TestStorableFile();
        $storableFile2->filename = "test.txt";
        $storableFile2->store(false, "test");

        $storableFile3 = new TestStorableFile();
        $storableFile3->filename = "test.jpg";
        $storableFile3->store(false, "test");
        $this->assertStringContainsString(
            '<framelix-image',
            $storableFile3->getImageTag(true, true, 1000, 1200, true, true)
        );


        $this->assertInstanceOf(Url::class, $storableFile->getDownloadUrl());
        $this->assertIsString($storableFile->getHtmlString());
        $this->assertSame('test', $storableFile->getFileContents());
        $this->assertSame('test', $storableFile2->getFileContents());
        $storableFile->delete();
        $storableFile2->delete();
        $this->assertNull($storableFile2->getDownloadUrl());
        // deleted file return html string anyway
        $this->assertIsString($storableFile->getHtmlString());

        $this->addSimulatedFile("test.txt", "foobar", false);
        $uploadedFile = UploadedFile::createFromSubmitData("test.txt")[0];
        $storableFile = new TestStorableFile();
        $storableFile->store(false, $uploadedFile);

        // restore to test update functionality
        $this->addSimulatedFile("test.txt", "foobar", false);
        $uploadedFile = UploadedFile::createFromSubmitData("test.txt")[0];
        $storableFile->store(false, $uploadedFile);
        $storableFile->store(false, "foobar2");
        $this->assertSame('foobar2', $storableFile->getFileContents());

        // only update metadata without file changes
        $storableFile->filename = "foo";
        $storableFile->store();

        $storableFile->delete();

        $this->assertNull($storableFile->getFileContents());

        $storableFile = new TestStorableFile();
        FileUtils::deleteDirectory(FRAMELIX_USERDATA_FOLDER . "/" . $storableFile->relativePathOnDisk);
    }
}