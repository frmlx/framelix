<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Exception\FatalError;

use function explode;
use function file_exists;
use function file_put_contents;
use function implode;
use function is_numeric;
use function strtolower;
use function unlink;

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
     * @param string $fit Sharp parameter, see https://sharp.pixelplumbing.com/api-resize#resize
     * @param string $position Sharp parameter, see https://sharp.pixelplumbing.com/api-resize#resize
     * @return Shell Return the shell command that have been executed
     */
    public static function resize(string $srcPath, string $dstPath, int $maxWidth, int $maxHeight, string $fit = "inside", string $position = "centre"): Shell
    {
        if (file_exists($dstPath)) {
            unlink($dstPath);
        }
        return self::executeNodeJsSharpCmd(
        /**@lang JavaScript */ '
          sharp(' . JsonUtils::encode($srcPath) . ').resize(' . JsonUtils::encode(
                ["width" => $maxWidth, "height" => $maxHeight, "fit" => $fit, "position" => $position]
            ) . ').toFile(' . JsonUtils::encode($dstPath) . ')
        '
        );
    }

    /**
     * Compress an image, can also convert on the file to new filetype
     * @param string $srcPath source path of image
     * @param string $dstPath destination path of image Only png, jpeg, jpg, gif and webp is supported
     * @return Shell Return the shell command that have been executed
     */
    public static function compress(string $srcPath, string $dstPath): Shell
    {
        $extension = strtolower(pathinfo($dstPath, PATHINFO_EXTENSION));
        $code = /**@lang JavaScript */
            'const img = sharp(' . JsonUtils::encode($srcPath) . ');';
        if ($extension === "png") {
            $code .= "img.png({quality:80});";
        }
        if ($extension === "jpg" || $extension === "jpeg") {
            $code .= "img.jpeg({quality:80, mozjpeg:true});";
        }
        if ($extension === "gif") {
            $code .= "img.gif();";
        }
        if ($extension === "webp") {
            $code .= "img.webp({quality:80});";
        }
        $code .= "img.toFile(" . JsonUtils::encode($dstPath) . ");";
        return self::executeNodeJsSharpCmd($code);
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

    public static function executeNodeJsSharpCmd(string $jsCode): Shell
    {
        $tmpFolder = FileUtils::getTmpFolder();
        $tmpFile = $tmpFolder . "/sharp.js";

        $jsCode = 'const sharp = require("' . (__DIR__ . "/../../node_modules/sharp/lib/index.js") . '");' . "\n" . $jsCode;
        file_put_contents($tmpFile, $jsCode);

        $shell = Shell::prepare("node {*}", [$tmpFile]);
        $shell->execute();
        return $shell;
    }

}
