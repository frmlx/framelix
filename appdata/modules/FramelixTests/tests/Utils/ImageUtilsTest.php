<?php

namespace Utils;

use Framelix\Framelix\Utils\ImageUtils;
use Framelix\FramelixTests\TestCase;

use function unlink;

final class ImageUtilsTest extends TestCase
{

    public const TESTFILES_ROOT = __DIR__ . "/../../tmp";

    public function testCompare(): void
    {
        $this->assertTrue(
            ImageUtils::compareImages(
                self::TESTFILES_ROOT . "/imageutils/same-1.png",
                self::TESTFILES_ROOT . "/imageutils/same-2.png"
            )
        );
        $this->assertFalse(
            ImageUtils::compareImages(
                self::TESTFILES_ROOT . "/imageutils/same-1.png",
                self::TESTFILES_ROOT . "/imageutils/diff.png"
            )
        );
        $this->assertTrue(
            ImageUtils::compareImages(
                self::TESTFILES_ROOT . "/imageutils/same-1.png",
                self::TESTFILES_ROOT . "/imageutils/diff.png",
                0.1
            )
        );
    }

    public function testImageData(): void
    {
        $this->assertEquals(['type' => 'jpeg', 'width' => 275, 'height' => 183],
            ImageUtils::getImageData(self::TESTFILES_ROOT . "/imageutils/test-image.jpg"));
        $this->assertEquals(['type' => 'png', 'width' => 225, 'height' => 225],
            ImageUtils::getImageData(self::TESTFILES_ROOT . "/imageutils/test-image.png"));
        $this->assertNull(ImageUtils::getImageData(__DIR__ . "/../../test-files/test-files.zip"));
    }

    public function testResize(): void
    {
        $testImage = self::TESTFILES_ROOT . "/imageutils/test-image.jpg";
        $testResizedImage = self::TESTFILES_ROOT . "/imageutils/test-image-resized.jpg";
        ImageUtils::resize($testImage, $testResizedImage, 100, 100);
        $this->assertEquals(['type' => 'jpeg', 'width' => 100, 'height' => 67],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);


        $testImage = self::TESTFILES_ROOT . "/imageutils/test-image.png";
        $testResizedImage = self::TESTFILES_ROOT . "/imageutils/test-image-resized.png";
        ImageUtils::resize($testImage, $testResizedImage, 100, 100);
        $this->assertEquals(['type' => 'png', 'width' => 100, 'height' => 100],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);
    }

    public function testPdfToImage(): void
    {
        $testPdf = self::TESTFILES_ROOT . "/imageutils/test-pdf.pdf";
        $testResizedImage = self::TESTFILES_ROOT . "/imageutils/test-image-convert.jpg";

        ImageUtils::convertPdfToImage($testPdf, $testResizedImage, true);
        $this->assertEquals(['type' => 'jpeg', 'width' => 1814, 'height' => 1304],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);

        ImageUtils::convertPdfToImage($testPdf, $testResizedImage, false);
        $this->assertEquals(['type' => 'jpeg', 'width' => 2381, 'height' => 3080],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);
    }
}
