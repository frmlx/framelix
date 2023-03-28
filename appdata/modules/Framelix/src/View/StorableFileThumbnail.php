<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\ImageUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View;

use function file_exists;
use function filesize;
use function in_array;
use function unlink;

/**
 * This view does generate thumbnails of storable files dynamically on-demand
 */
class StorableFileThumbnail extends View
{
    protected string|bool $accessRole = "*";
    protected ?string $customUrl = "~/storablefilethumbnail/(?<id>[0-9]+)-(?<thumbSize>[0-9]+)\.(?<extension>[a-z]+)$~";
    protected bool $multilanguage = false;

    public function onRequest(): void
    {
        $thumbSize = (int)$this->customUrlParameters['thumbSize'];
        $file = StorableFile::getById((int)$this->customUrlParameters['id'], withChilds: true);
        if (!$file || !file_exists($file->getPath()) || !$file->isThumbnailable() || !in_array(
                $thumbSize,
                $file->thumbSizes
            )) {
            http_response_code(404);
            return;
        }
        $thumbPath = $file->getThumbPath($thumbSize);
        if (file_exists($thumbPath)) {
            $file->getPublicUrl($thumbSize)->redirect(301);
        }
        $originalPath = $file->getPath();
        ImageUtils::resize($originalPath, $thumbPath, $thumbSize, $thumbSize);
        // if thumb is the same as original, or filesize is bigger after resize, just symlink to original
        $imageDataOriginal = ImageUtils::getImageData($originalPath);
        $imageDataThumb = ImageUtils::getImageData($thumbPath);
        if ($imageDataOriginal === $imageDataThumb || filesize($thumbPath) >= filesize($originalPath)) {
            unlink($thumbPath);
            Shell::prepare('ln -s {*}', [$originalPath, $thumbPath])->execute();
        }
        $file->getPublicUrl($thumbSize)->redirect(301);
    }
}