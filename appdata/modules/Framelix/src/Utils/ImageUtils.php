<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Exception\FatalError;

use function end;
use function explode;
use function implode;
use function is_numeric;
use function strtolower;

class ImageUtils
{

    /**
     * Get image data for given file
     * @param string $path Path to image file
     * @return array{type: string, width: int, height:int}|null (type is always lowercase)
     */
    public static function getImageData(string $path): ?array
    {
        $shell = Shell::prepare('identify {*}', [$path]);
        $shell->execute();
        if ($shell->status) {
            return null;
        }
        $data = explode(" ", $shell->output[0]);
        $size = explode("x", $data[2]);
        $arr = [];
        $arr['type'] = strtolower($data[1]);
        $arr['width'] = (int)$size[0];
        $arr['height'] = (int)$size[1];
        return $arr;
    }

    /**
     * Resizes an image from $srcPath to $dstPath to fit into maxWidth and maxHeight
     * @param string $srcPath source path of image
     * @param string $dstPath destination path of image
     * @param int $maxWidth Max width of image in pixels
     * @param int $maxHeight Max height of image in pixels
     * @return Shell Return the shell command that have been executed
     */
    public static function resize(string $srcPath, string $dstPath, int $maxWidth, int $maxHeight): Shell
    {
        $pathParts = explode(".", mb_strtolower($srcPath));
        $extension = end($pathParts);

        $shell = Shell::prepare(
          'convert {*}',
          [
            $srcPath . ($extension == "gif" ? '[0]' : ''),
            '-resize',
            "{$maxWidth}x{$maxHeight}>",
            '-quality',
            '85',
            $dstPath,
          ]
        );
        $shell->execute();
        return $shell;
    }

    /**
     * Convert a PDF to an image
     * @param string $pdfPath
     * @param string $imagePath Must end with .jpg or .png
     * @param bool $trimWhitespaceAround If true, it will remove all empty border around the content (Useful to extract
     *   a single image out of pdf)
     * @param int $density Lower = lower resolution
     * @return Shell Return the shell command that have been executed
     */
    public static function convertPdfToImage(
      string $pdfPath,
      string $imagePath,
      bool $trimWhitespaceAround,
      int $density = 280
    ): Shell {
        $cmd = "convert -density $density {0} ";
        if ($trimWhitespaceAround) {
            $cmd .= " -flatten -fuzz 1% -trim +repage ";
        }
        $cmd .= "-quality 20 {1}";
        $shell = Shell::prepare($cmd, [$pdfPath, $imagePath]);
        $shell->execute();
        return $shell;
    }

    /**
     * Compare image file a with file b and return true if they match by given treshold
     * Passing 0 to $treshold means 100% exact the same, this only will work for PNG images, JPG have always different
     * noise in it
     * @param string $imagePathExpected
     * @param string $imagePathActual
     * @param float $threshold The difference in pixel percentage from 0 to 1 (1 = 100% difference, 0 = exact same
     *   image)
     * @return bool
     */
    public static function compareImages(
      string $imagePathExpected,
      string $imagePathActual,
      float $threshold = 0
    ): bool {
        $imageDataExpected = self::getImageData($imagePathExpected);
        $imageDataActual = self::getImageData($imagePathActual);
        if ($imageDataExpected !== $imageDataActual) {
            return false;
        }
        $cmd = "compare -define png:color-type=6 -metric AE  {0} {1} NULL:";
        $shell = Shell::prepare($cmd, [$imagePathExpected, $imagePathActual]);
        $shell->execute();
        $diffPixels = $shell->output[0] ?? -1;
        if ($diffPixels <= -1 || !is_numeric($diffPixels)) {
            throw new FatalError('Image compare failed: ' . implode("<br/>", $shell->output));
        }
        $diffPixels = (int)$diffPixels;
        $imageData = self::getImageData($imagePathExpected);
        $expectedSizePixels = $imageData['width'] * $imageData['height'];
        $diff = 1 / $expectedSizePixels * $diffPixels;
        return $diff <= $threshold;
    }

}
