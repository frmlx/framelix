<?php

namespace Utils;

use Framelix\Framelix\Utils\ImageUtils;
use Framelix\FramelixTests\TestCase;

use function filesize;
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
        $this->assertNull(ImageUtils::getImageData(__DIR__ . "/../../test-files/test-files.zip"));
    }

    public function testResize(): void
    {
        $testImage = __DIR__ . "/../../test-files/imageutils/test-image.jpg";
        $testResizedImage = FRAMELIX_TMP_FOLDER . "/test-image-resized.jpg";
        $shell = ImageUtils::resize($testImage, $testResizedImage, 100, 100);
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

    public function testCompress(): void
    {
        $testImage = __DIR__ . "/../../test-files/imageutils/test-image-uncompressed.jpg";
        $testImageCompressed = FRAMELIX_TMP_FOLDER . "/test-image-compressed.jpg";
        ImageUtils::compress($testImage, $testImageCompressed);
        $this->assertTrue(filesize($testImageCompressed) < filesize($testImage));
        unlink($testImageCompressed);

        $testImage = __DIR__ . "/../../test-files/imageutils/test-image-uncompressed.png";
        $testImageCompressed = FRAMELIX_TMP_FOLDER . "/test-image-compressed.png";
        ImageUtils::compress($testImage, $testImageCompressed);
        $this->assertTrue(filesize($testImageCompressed) < filesize($testImage));
        unlink($testImageCompressed);

        $testImage = __DIR__ . "/../../test-files/imageutils/test-image-uncompressed.png";
        $testImageCompressed = FRAMELIX_TMP_FOLDER . "/test-image-compressed.webp";
        ImageUtils::compress($testImage, $testImageCompressed);
        $this->assertTrue(filesize($testImageCompressed) < filesize($testImage));
        unlink($testImageCompressed);

        $testImage = __DIR__ . "/../../test-files/imageutils/test-image-uncompressed.png";
        $testImageCompressed = FRAMELIX_TMP_FOLDER . "/test-image-compressed.gif";
        ImageUtils::compress($testImage, $testImageCompressed);
        $this->assertTrue(filesize($testImageCompressed) < filesize($testImage));
        unlink($testImageCompressed);
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
