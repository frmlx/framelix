<?php

namespace Db\Storables;

use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\Storable\TestStorableFile;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class StorableFileTestBase extends TestCaseDbTypes
{

    public function test(): void
    {
        FileUtils::deleteDirectory(FileUtils::getUserdataFilepath("storablefile", true));
        $this->setupDatabase();
        $this->addSimulatedFile("test.txt", "foobar", false);

        $uploadedPdfFile = UploadedFile::createFromFile(__DIR__ . "/../../../test-files/imageutils/test-pdf.pdf");
        $uploadedImageFile = UploadedFile::createFromFile(__DIR__ . "/../../../test-files/imageutils/test-image.jpg");

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

        $storableFilePdf = new TestStorableFile();
        $storableFilePdf->store(false, $uploadedPdfFile, true);

        $storableFilePdf2 = new TestStorableFile();
        $storableFilePdf2->store(false, $uploadedPdfFile, true);

        $storableFileImg = new TestStorableFile();
        $storableFileImg->store(false, $uploadedImageFile, true);
        $this->assertStringContainsString(
            '<framelix-image',
            $storableFileImg->getImageTag(true, true, 1000, 1200, true, true)
        );

        $this->assertInstanceOf(Url::class, $storableFilePdf->getDownloadUrl());
        $this->assertIsString($storableFilePdf->getHtmlString());
        $this->assertSame($uploadedPdfFile->getFileContents(), $storableFilePdf->getFileContents());
        $this->assertSame($uploadedPdfFile->getFileContents(), $storableFilePdf2->getFileContents());

        $this->assertIsArray($storableFilePdf->getMetadata());
        $this->assertSame(['width' => 275, 'height' => 183], $storableFileImg->getImageSize());
        $this->assertSame(['width' => 100, 'height' => 67], $storableFileImg->getImageSize(100));
        $this->assertInstanceOf(TableCell::class, $storableFileImg->getHtmlTableValue());

        $storableFilePdf->delete();
        $storableFilePdf2->delete();
        $this->assertNull($storableFilePdf2->getDownloadUrl());
        // deleted file return html string anyway
        $this->assertIsString($storableFilePdf->getHtmlString());

        $this->addSimulatedFile("test.txt", "foobar", false);
        $uploadedFile = UploadedFile::createFromSubmitData("test.txt")[0];
        $storableFilePdf = new TestStorableFile();
        $storableFilePdf->store(false, $uploadedFile);

        // restore to test update functionality
        $this->addSimulatedFile("test.txt", "foobar", false);
        $uploadedFile = UploadedFile::createFromSubmitData("test.txt")[0];
        $storableFilePdf->store(false, $uploadedFile);
        $storableFilePdf->store(false, "foobar2");
        $this->assertSame('foobar2', $storableFilePdf->getFileContents());

        // only update metadata without file changes
        $storableFilePdf->filename = "foo";
        $storableFilePdf->store();

        $storableFilePdf->delete();

        $this->assertNull($storableFilePdf->getFileContents());
        FileUtils::deleteDirectory(FileUtils::getUserdataFilepath("storablefile", true));
    }

}