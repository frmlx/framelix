<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\StorableFolder;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\QuickCast;

use function array_filter;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_string;

use const FRAMELIX_MODULE;

class MediaBrowserSelection implements StorablePropertyInterface
{
    public array $selection = [];
    public array $sortedFiles = [];
    public array $cache = [];

    /**
     * Setup the property database schema definition to this storable property itself
     * This defines how the column will be created in the database
     * @param StorableSchemaProperty $storableSchemaProperty
     */
    public static function setupSelfStorableSchemaProperty(StorableSchemaProperty $storableSchemaProperty): void
    {
        $storableSchemaProperty->databaseType = "longtext";
    }

    /**
     * Create an instance from the original database value
     * @param mixed $dbValue
     * @return self|null
     */
    public static function createFromDbValue(mixed $dbValue): ?self
    {
        return self::create($dbValue);
    }

    /**
     * Create an instance from a submitted form value
     * @param mixed $formValue
     * @return self|null
     */
    public static function createFromFormValue(mixed $formValue): ?self
    {
        return self::create($formValue);
    }

    /**
     * Create an instance from a value
     * @param mixed $value
     * @return self|null
     */
    public static function create(mixed $value): ?self
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof self) {
            return $value;
        }
        if (is_string($value)) {
            $value = JsonUtils::decode($value);
        }
        if (!is_array($value) || !$value) {
            return null;
        }
        $instance = new self();
        $instance->selection = array_values(
            array_filter(QuickCast::to($value['selection'] ?? [], 'int', emptyToNull: true))
        );
        $instance->sortedFiles = array_values(
            array_filter(QuickCast::to($value['sortedFiles'] ?? [], 'int', emptyToNull: true))
        );
        if (!$instance->selection && !$instance->sortedFiles) {
            return null;
        }
        return $instance;
    }

    /**
     * Get the first file of the selection
     * @return StorableFile|null
     */
    public function getSelectionFirstFile(): ?StorableFile
    {
        return reset($this->getSelectionFoldersAndFiles()['files']) ?: null;
    }

    /**
     * Get all folders and files for selection recursively
     * Empty selected folders will not be returned
     * foldersCount are also count a file without a folder (root)
     * @return array{folders:StorableFolder[], foldersCount:int, files: StorableFile[]}|null
     */
    public function getSelectionFoldersAndFiles(): ?array
    {
        $cacheKey = __METHOD__;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }
        if (!$this->selection) {
            $this->cache[$cacheKey] = null;
            return $this->cache[$cacheKey];
        }
        $selectedStorables = Storable::getByCondition('id IN {0}', [$this->selection], connectionId: FRAMELIX_MODULE);
        $files = [];
        $folders = [];
        foreach ($selectedStorables as $selectedStorable) {
            if ($selectedStorable instanceof StorableFile) {
                $files[$selectedStorable->id] = $selectedStorable;
                $folders[$selectedStorable->storableFolder->id ?? 0] = $selectedStorable->storableFolder;
            } elseif ($selectedStorable instanceof StorableFolder) {
                $childs = $selectedStorable->getChilds(true);
                foreach ($childs as $child) {
                    if ($child instanceof StorableFile) {
                        $files[$child->id] = $child;
                        $folders[$child->storableFolder->id ?? 0] = $child->storableFolder;
                    }
                }
            }
        }
        $foldersCount = count($folders);
        unset($folders[0]);
        $files = $this->sortFiles($files);
        $this->cache[$cacheKey] = ['folders' => $folders, 'foldersCount' => $foldersCount, 'files' => $files];
        return $this->cache[$cacheKey];
    }

    /**
     * Sort files by user sort setting
     * @param StorableFile[] $files
     * @return StorableFile[]
     */
    public function sortFiles(array &$files): array
    {
        $sortArr = $this->sortedFiles;
        $newArr = [];
        foreach ($sortArr as $id) {
            if (isset($files[$id])) {
                $newArr[$id] = $files[$id];
                unset($files[$id]);
            }
        }
        foreach ($files as $file) {
            $newArr[$file->id] = $file;
        }
        return $newArr;
    }


    public function getDbValue(): ?string
    {
        return $this->selection || $this->sortedFiles ? JsonUtils::encode($this) : null;
    }

    public function getHtmlString(): string
    {
        return $this->getDbValue() ?? '';
    }

    public function getHtmlTableValue(): string
    {
        return $this->getHtmlString();
    }

    public function getRawTextString(): string
    {
        return $this->getHtmlTableValue();
    }

    public function getSortableValue(): int
    {
        return 0;
    }

    public function __toString()
    {
        return $this->getDbValue() ?? '';
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->selection && !$this->sortedFiles) {
            return null;
        }
        return ['selection' => $this->selection, 'sortedFiles' => $this->sortedFiles];
    }

}