<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\ImageUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View;

use function ceil;
use function clearstatcache;
use function copy;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function http_response_code;
use function in_array;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function strrpos;
use function strtolower;
use function substr;
use function unlink;

/**
 * A storable file to store on disk
 * @property StorableFolder|null $storableFolder
 * @property string $filename
 * @property string|null $extension
 * @property string $relativePathOnDisk
 * @property int $filesize
 * @property Storable|null $assignedStorable
 * @property int $fileNr Internal file nr counter for all files that are stored
 */
class StorableFile extends StorableExtended
{
    /**
     * Extensions for that we can generate thumbnails
     * @var string[]
     */
    public const array THUMBNAIL_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'webp'];

    /**
     * Extensions that can be viewed safely in today's browsers
     * @var string[]
     */
    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'apng', 'png', 'webp', 'bmp', 'svg'];

    /**
     * Extensions that can be viewed safely in today's' browsers
     * @var string[]
     */
    public const array VIDEO_EXTENSIONS = ['mp4', 'webm'];

    /**
     * All available thumb sizes
     * Can be override in child class
     * @var int[]
     */
    public array $thumbSizes = [
        100,
        500,
        1000,
        1500,
        2000,
        3000
    ];

    /**
     * Cached metadata
     * @var array|null
     */
    private ?array $metadata = null;

    /**
     * Max files in a single subfolder
     * If limit is reached, it does create a new folder
     * @var int
     */
    protected int $maxFilesPerFolder = 1000;

    /**
     * Keep file extensions on disk
     * The files that are not matches these extensions stored on disk as {filename}.txt to prevent any abuse
     * By default, only images/videos are considered safe to keep and they are useful when you want link them directly on a website
     * @var string[]
     */
    protected array $keepFileExtensions = ['jpg', 'jpeg', 'gif', 'png', 'apng', 'svg', 'webp', 'mp4', 'webm'];

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'downloadFile') {
            $file = StorableFile::getById(
                Request::getGet('id'),
                Request::getGet('connectionId'),
                true
            );
            if (!$file) {
                http_response_code(404);
                return;
            }
            Response::download($file);
        }
    }

    /**
     * Get the absolute path to the userdata folder where the files for this storable should be stored
     * @param bool $public Use public folder, instead of private
     * @return string
     */
    public static function getUserdataFolderPath(bool $public = false): string
    {
        return FileUtils::getUserdataFilepath("storablefile", $public);
    }

    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['filesize']->databaseType = 'bigint';
        $selfStorableSchema->properties['filesize']->unsigned = true;
    }

    public function isVideo(): bool
    {
        return in_array($this->extension, self::VIDEO_EXTENSIONS);
    }

    public function isThumbnailable(): bool
    {
        return in_array($this->extension, self::THUMBNAIL_EXTENSIONS);
    }

    public function isImage(): bool
    {
        return in_array($this->extension, self::IMAGE_EXTENSIONS);
    }

    /**
     * Get framelix image tag that display this image responsive and lazy loaded
     * @param bool $lazy
     * @param bool $recalculate The image is placed in a resizable container. Automatically recalculate matched thumbnail size after resize.
     * @param int $minWidth In pixel
     * @param int $maxWidth In pixel
     * @param bool $setParent Set the image url into the parents css background-image instead of an explicit image tag
     * @param bool $antiCacheParameter Append a ".fac." to the filename representing the filetime in crc32
     * @param array|null $additionalAttributes Add additional html attributes
     * @return string
     */
    public function getImageTag(
        bool $lazy = true,
        bool $recalculate = false,
        int $minWidth = 0,
        int $maxWidth = 0,
        bool $setParent = false,
        bool $antiCacheParameter = false,
        ?array $additionalAttributes = null
    ): string {
        $attr = new HtmlAttributes();
        foreach ($this->thumbSizes as $size) {
            $attr->set('size-' . $size, $this->getPublicUrl($size, $antiCacheParameter));
        }
        if (!$lazy) {
            $attr->set('nolazy', true);
        }
        if ($recalculate) {
            $attr->set('recalculate', true);
        }
        if ($minWidth) {
            $attr->set('minwidth', $minWidth);
        }
        if ($maxWidth) {
            $attr->set('maxwidth', $maxWidth);
        }
        if ($setParent) {
            $attr->set('setparent', true);
        }
        $attr->set('src', $this->getPublicUrl(null, $antiCacheParameter));
        if ($additionalAttributes) {
            $attr->setArray($additionalAttributes);
        }
        return '<framelix-image ' . $attr . '></framelix-image>';
    }

    /**
     * Just let the browser download this file
     * @param string|null $filename Override filename
     */
    public function download(?string $filename = null): never
    {
        Response::download($this, $filename);
    }

    /**
     * Get download url
     * Return null if you want to disable download functionality
     * @return Url|null
     */
    public function getDownloadUrl(): ?Url
    {
        if (!$this->id) {
            return null;
        }
        return JsCall::getUrl([self::class, "onJsCall"], "downloadFile",
            ["id" => $this->id, "connectionId" => $this->connectionId]);
    }

    /**
     * Get public url to this file
     * @param int|null $thumbSize Only take effect when isThumbnailable() is true
     * @param bool $antiCacheParameter Append a ".fac." to the filename representing the filetime in crc32
     * @return Url
     */
    public function getPublicUrl(?int $thumbSize = null, bool $antiCacheParameter = false): Url
    {
        $file = $this->getPath();
        $url = Url::getUrlToPublicFile($file, $antiCacheParameter);
        if ($thumbSize && $this->isThumbnailable()) {
            $thumbPath = $this->getThumbPath($thumbSize);
            if ($thumbPath && is_file($thumbPath)) {
                return Url::getUrlToPublicFile($thumbPath, $antiCacheParameter);
            }
            $url = View::getUrl(
                View\StorableFileThumbnail::class,
                [
                    'id' => $this->id,
                    'extension' => $this->extension,
                    'thumbSize' => $thumbSize
                ]
            );
        }
        return $url;
    }

    /**
     * Get thumbnail path
     * It is not guarenteed that this file exist on disk when the path is returned
     * @param int $thumbSize
     * @return string|null Does return null when file is not saved in database
     */
    public function getThumbPath(int $thumbSize): ?string
    {
        if (!$this->id && !$this->relativePathOnDisk) {
            return null;
        }
        $path = $this::getUserdataFolderPath() . "/" . $this->relativePathOnDisk;
        $basename = basename($path);
        return dirname($path) . "/t-" . $thumbSize . "-" . $basename;
    }


    /**
     * Get path to the file on disk
     * @param bool $fileCheck If true, then does return the path only if the file really exists
     * @return string|null Null if fileCheck is enabled a file do not exist on disk
     */
    public function getPath(bool $fileCheck = true): ?string
    {
        if (!$this->relativePathOnDisk) {
            return null;
        }
        $path = $this::getUserdataFolderPath() . "/" . $this->relativePathOnDisk;
        if ($fileCheck && !is_file($path)) {
            return null;
        }
        return $path;
    }

    /**
     * Get file contents as string
     * @return string|null
     */
    public function getFileContents(): ?string
    {
        $path = $this->getPath();
        if ($path) {
            return file_get_contents($path);
        }
        return null;
    }

    /**
     * Get metadata to given file and create metadata file if not yet exist
     * @return array{image:array{width:int, height: int}}|null
     */
    public function getMetadata(): ?array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }
        $metaFile = $this->getPath() . ".meta.json";
        if (!is_file($metaFile)) {
            $this->createMetadataFile();
        }
        $this->metadata = JsonUtils::readFromFile($metaFile);
        return $this->metadata;
    }

    /**
     * Return the image size for this file, if it is an image for what we can calculate size
     * @param int|null $thumbnailSize If set, return calculated size for the thumbnail of this length
     * @return array{width:int, height: int}|null
     */
    public function getImageSize(?int $thumbnailSize = null): ?array
    {
        if (!$this->isThumbnailable()) {
            return null;
        }
        $imageSize = $this->getMetadata()['image'] ?? null;
        if (!$imageSize) {
            return null;
        }
        if ($thumbnailSize) {
            $width = $imageSize['width'];
            $height = $imageSize['height'];
            if ($width > $height) {
                $thumbnailWidth = $thumbnailSize;
                $thumbnailHeight = ceil($height / $width * $thumbnailSize);
            } elseif ($width < $height) {
                $thumbnailHeight = $thumbnailSize;
                $thumbnailWidth = ceil($width / $height * $thumbnailSize);
            } else {
                $thumbnailWidth = $thumbnailSize;
                $thumbnailHeight = $thumbnailSize;
            }
            return ['width' => (int)$thumbnailWidth, 'height' => (int)$thumbnailHeight];
        }
        return $imageSize;
    }

    public function createMetadataFile(): void
    {
        $metaFile = $this->getPath(false) . ".meta.json";
        $this->metadata = [];
        $path = $this->getPath();
        if ($path && $this->isImage()) {
            $imageData = ImageUtils::getImageData($path);
            if ($imageData) {
                $width = $imageData['width'];
                $height = $imageData['height'];
                $this->metadata = [
                    "image" => [
                        "width" => $width,
                        "height" => $height
                    ]
                ];
            }
        }
        JsonUtils::writeToFile($metaFile, $this->metadata);
    }

    public function getRawTextString(): string
    {
        return $this->filename ?? '';
    }

    public function getHtmlString(): string
    {
        return $this->getRawTextString();
    }

    public function getHtmlTableValue(): TableCell|string
    {
        $downloadUrl = $this->getDownloadUrl();
        if (!$downloadUrl) {
            return $this->getRawTextString();
        }
        return TableCell::create('<a href="' . $downloadUrl . '" title="__framelix_downloadfile__" download class="framelix-storable-file-download">' .
            HtmlUtils::getFramelixIcon('709') . ' <span>' .
            HtmlUtils::escape($this->filename) . '</span></a>');
    }

    /**
     * Store with given file
     * If UploadedFile is given, it does MOVE or COPY, depending on the third param
     * @param bool $force Force store even if isEditable() is false
     * @param UploadedFile|string|null $file String is considered as binary filedata
     * @param bool $copy If $file is UploadedFile, copy the file instead of moving
     */
    public function store(bool $force = false, UploadedFile|string|null $file = null, bool $copy = false): void
    {
        if (!$force && !$this->isEditable()) {
            throw new FatalError(
                "Storable #" . $this . " (" . $this->getRawTextString() . ") is not editable"
            );
        }
        if ($file instanceof UploadedFile && !is_file($file->path)) {
            throw new FatalError(
                "Couldn't store StorableFile because uploaded file does not exist"
            );
        }
        if ($file === null && !$this->id) {
            throw new FatalError(
                "Couldn't store StorableFile because no file is given"
            );
        }
        if ($file instanceof UploadedFile && !$this->filename) {
            $this->filename = $file->name;
        }
        if (!$this->filename) {
            throw new FatalError("You need to set a filename");
        }
        $lastPoint = strrpos($this->filename, ".");
        $this->filename = substr($this->filename, -190);
        if ($lastPoint !== false) {
            $this->extension = substr(strtolower(substr($this->filename, $lastPoint + 1)), 0, 20);
        }
        // no file given, store just the metadata
        if ($file === null) {
            parent::store($force);
            return;
        }
        $isNew = !$this->id;
        if ($isNew) {
            $lastFile = static::getByConditionOne(sort: ['-id']);
            $fileNr = 1;
            if ($lastFile) {
                $fileNr = $lastFile->fileNr + 1;
            }
            $extensionOnDisk = (in_array($this->extension, $this->keepFileExtensions) ? $this->extension : 'txt');
            $folderNameCounter = ceil($fileNr / 1000) * 1000;
            while (true) {
                $this->relativePathOnDisk =
                    $folderNameCounter .
                    "/" . RandomGenerator::getRandomString(30, 40) .
                    "." . $extensionOnDisk;
                // file not exist, break the loop
                if (!$this->getPath()) {
                    break;
                }
            }

            $folder = dirname($this->getPath(false));
            if (!is_dir($folder)) {
                mkdir($folder, recursive: true);
                clearstatcache();
            }
            $this->fileNr = $fileNr;
        }
        $path = $this->getPath(false);
        if ($file instanceof UploadedFile) {
            if (!copy($file->path, $path)) {
                // @codeCoverageIgnoreStart
                throw new FatalError("Couldn't copy file to destination folder");
                // @codeCoverageIgnoreEnd
            }
            if (!$copy && is_file($file->path)) {
                unlink($file->path);
            }
            $this->filesize = $file->size;
        } elseif (is_string($file)) {
            file_put_contents($path, $file);
            $this->filesize = filesize($path);
        }
        if ($file) {
            $this->createMetadataFile();
        }
        parent::store($force);
        if (!$isNew) {
            $this->deleteThumbnailFiles();
        }
    }

    public function delete(bool $force = false): void
    {
        $path = $this->getPath();
        $metaFile = $this->getPath(false) . ".meta.json";
        parent::delete($force);
        $this->deleteThumbnailFiles();
        if ($path) {
            unlink($path);
        }
        if (is_file($metaFile)) {
            unlink($metaFile);
        }
    }

    private function deleteThumbnailFiles(): void
    {
        foreach ($this->thumbSizes as $thumbSize) {
            $thumbPath = $this->getThumbPath($thumbSize);
            if ($thumbPath && is_file($thumbPath)) {
                unlink($thumbPath);
            }
        }
    }
}