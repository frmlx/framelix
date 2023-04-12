<?php

namespace Utils;

use Framelix\Framelix\Utils\ImageUtils;
use Framelix\FramelixTests\TestCase;

use function unlink;

use const FRAMELIX_TMP_FOLDER;

final class ImageUtilsTest extends TestCase
{
    public function testCompare(): void
    {
        $this->assertTrue(
            ImageUtils::compareImages(
                __DIR__ . "/../../test-files/imageutils/same-1.png",
                __DIR__ . "/../../test-files/imageutils/same-2.png",
            )
        );
        $this->assertFalse(
            ImageUtils::compareImages(
                __DIR__ . "/../../test-files/imageutils/same-1.png",
                __DIR__ . "/../../test-files/imageutils/diff.png",
            )
        );
        $this->assertTrue(
            ImageUtils::compareImages(
                __DIR__ . "/../../test-files/imageutils/same-1.png",
                __DIR__ . "/../../test-files/imageutils/diff.png",
                0.1
            )
        );
    }

    public function testImageData(): void
    {
        $this->assertEquals(['type' => 'jpeg', 'width' => 275, 'height' => 183],
            ImageUtils::getImageData(__DIR__ . "/../../test-files/imageutils/test-image.jpg"));
        $this->assertEquals(['type' => 'png', 'width' => 225, 'height' => 225],
            ImageUtils::getImageData(__DIR__ . "/../../test-files/imageutils/test-image.png"));
        $this->assertIsString(ImageUtils::getImageData(__DIR__ . "/../../test-files/test-files.zip"));
    }

    public function testResize(): void
    {
        $testImage = __DIR__ . "/../../test-files/imageutils/test-image.jpg";
        $testResizedImage = FRAMELIX_TMP_FOLDER . "/test-image-resized.jpg";
        ImageUtils::resize($testImage, $testResizedImage, 100, 100);
        $this->assertEquals(['type' => 'jpeg', 'width' => 100, 'height' => 67],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);


        $testImage = __DIR__ . "/../../test-files/imageutils/test-image.png";
        $testResizedImage = FRAMELIX_TMP_FOLDER . "/test-image-resized.png";
        ImageUtils::resize($testImage, $testResizedImage, 100, 100);
        $this->assertEquals(['type' => 'png', 'width' => 100, 'height' => 100],
            ImageUtils::getImageData($testResizedImage));
        unlink($testResizedImage);
    }

    public function testPdfToImage(): void
    {
        $testPdf = __DIR__ . "/../../test-files/imageutils/test-pdf.pdf";
        $testResizedImage = FRAMELIX_TMP_FOLDER . "/test-image-convert.jpg";

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
