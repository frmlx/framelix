<?php

namespace Framelix\Framelix\Network;

use function file_get_contents;
use function is_array;
use function is_string;
use function strrpos;
use function strtolower;
use function substr;

/**
 * A uploaded file wrapper for $_FILES
 */
class UploadedFile
{
    /**
     * The uploaded file name
     * @var string
     */
    public string $name;

    /**
     * The tmp path to the file on disk
     * @var string
     */
    public string $path;

    /**
     * Filesize
     * @var int
     */
    public int $size;

    /**
     * The mime type
     * @var string
     */
    public string $type;

    /**
     * Create an instance from a file on disk
     * @param string $path
     * @return self
     */
    public static function createFromFile(string $path): self
    {
        $instance = new self();
        $instance->name = basename($path);
        $instance->path = $path;
        $instance->size = filesize($path);
        $instance->type = "other";
        return $instance;
    }

    /**
     * Return array of instances for all data in $_FILES
     * @param string $fieldName
     * @return self[]|null
     */
    public static function createFromSubmitData(string $fieldName): ?array
    {
        $submittedFiles = $_FILES[$fieldName] ?? null;
        if (!is_array($submittedFiles)) {
            return null;
        }
        if (is_string($submittedFiles['name'])) {
            $newArr = [];
            foreach ($submittedFiles as $key => $row) {
                $newArr[$key][0] = $row;
            }
            $submittedFiles = $newArr;
        }
        $arr = [];
        foreach ($submittedFiles['name'] as $fileKey => $name) {
            if ($submittedFiles['error'][$fileKey]) {
                continue;
            }
            $instance = self::createFromFile($submittedFiles['tmp_name'][$fileKey]);
            $instance->name = $name;
            $instance->type = $submittedFiles['type'][$fileKey];
            $arr[] = $instance;
        }
        return $arr ?: null;
    }

    /**
     * Get file contents
     * @return string
     */
    public function getFileContents(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Get file extension
     * @return string|null
     */
    public function getExtension(): ?string
    {
        $lastPoint = strrpos($this->name, ".");
        if ($lastPoint !== false) {
            return substr(strtolower(substr($this->name, $lastPoint + 1)), 0, 20);
        }
        return null;
    }
}