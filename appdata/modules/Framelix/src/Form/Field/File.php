<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;

use function is_array;
use function var_dump;

/**
 * A file upload field
 */
class File extends Field
{
    /**
     * Is multiple
     * @var bool
     */
    public bool $multiple = false;

    /**
     * Allowed file types
     * Example: Only allow images, use image/*, allow only certain file endings use .txt for example
     * @var string|null
     */
    public ?string $allowedFileTypes = null;

    /**
     * Min selected files for submitted value
     * @var int|null
     */
    public ?int $minSelectedFiles = null;

    /**
     * Max selected files for submitted value
     * @var int|null
     */
    public ?int $maxSelectedFiles = null;

    /**
     * Upload btn label
     * @var string
     */
    public string $buttonLabel = '__framelix_form_file_pick__';

    /**
     * Instant delete the existing file when user click the delete button
     * Otherwise you must implement a delete functionality
     * @var bool
     */
    public bool $instantDelete = false;

    /**
     * Storable file based used for store/delete functions
     * @var StorableFile|null
     */
    public ?StorableFile $storableFileBase = null;

    /**
     * Store and delete files based on submit data
     * @param Storable $storable
     * @param bool $storeUploads If true, store uploaded files
     * @param bool $deleteFiles If true, delete files that the user has marked to delete
     * @return array{created: StorableFile[], deleted: int}|null Null when no $this->storableFileBase isset
     */
    public function store(Storable $storable, bool $storeUploads = true, bool $deleteFiles = true): ?array
    {
        if (!$this->storableFileBase) {
            return null;
        }
        $uploadedFiles = UploadedFile::createFromSubmitData($this->name);
        $schemeProperty = $this->storableFileBase::getStorableSchemaProperty($storable, $this->name);
        // uploads
        $createdFiles = [];
        if ($storeUploads && $uploadedFiles) {
            $addTo = false;
            if ($schemeProperty) {
                $addTo = null;
                if ($schemeProperty->arrayStorableClass) {
                    $addTo = $storable->{$this->name} ?? [];
                }
            }
            foreach ($uploadedFiles as $uploadedFile) {
                $storableFile = $this->storableFileBase->clone();
                if (!$storableFile->assignedStorable) {
                    $storableFile->assignedStorable = $storable;
                }
                $storableFile->store(false, $uploadedFile);
                $createdFiles[$storableFile->id] = $storableFile;
                if (is_array($addTo)) {
                    $addTo[$storableFile->id] = $storableFile;
                } elseif ($addTo === null) {
                    $addTo = $storableFile;
                }
            }
            if ($addTo) {
                $storable->{$this->name} = $addTo;
                $storable->store();
            }
        }

        // deletions
        $deleted = 0;
        $deleteFlags = $this->getSubmittedValue();
        if ($deleteFiles && is_array($deleteFlags)) {
            $removeFrom = false;
            if ($schemeProperty) {
                $removeFrom = $storable->{$this->name};
            }
            foreach ($deleteFlags as $id => $flag) {
                if ($flag !== '0') {
                    continue;
                }
                $file = $this->storableFileBase::getById($id, $this->storableFileBase->connectionId, true);
                if (!$file) {
                    continue;
                }
                if (is_array($removeFrom)) {
                    foreach ($removeFrom as $k => $existingFile) {
                        if ($existingFile === $file) {
                            unset($removeFrom[$k]);
                        }
                    }
                } elseif ($removeFrom === $file) {
                    $removeFrom = null;
                }
                $file->delete();
                $deleted++;
            }
            if ($removeFrom !== false) {
                $storable->{$this->name} = $removeFrom ?: null;
                $storable->store();
            }
        }
        return [
            'created' => $createdFiles,
            'deleted' => $deleted
        ];
    }

    /**
     * Get default converted submitted value
     * @return UploadedFile[]|null
     */
    public function getDefaultConvertedSubmittedValue(): ?array
    {
        return UploadedFile::createFromSubmitData($this->name);
    }

    /**
     * Set allowing only images
     * @return void
     */
    public function setOnlyImages(): void
    {
        $this->allowedFileTypes = '.jpg, .jpeg, .gif, .png, .webp';
    }

    /**
     * Set allowing only videos
     * @return void
     */
    public function setOnlyVideos(): void
    {
        $this->allowedFileTypes = '.mp4, .webm';
    }

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = $this->getDefaultConvertedSubmittedValue();
        $count = is_array($value) ? count($value) : 0;
        if ($count) {
            if ($this->minSelectedFiles !== null && $count < $this->minSelectedFiles) {
                return Lang::get(
                    '__framelix_form_validation_minselectedfiles__',
                    ['number' => $this->minSelectedFiles]
                );
            }
            if ($this->maxSelectedFiles !== null && $count > $this->maxSelectedFiles) {
                return Lang::get(
                    '__framelix_form_validation_maxselectedfiles__',
                    ['number' => $this->maxSelectedFiles]
                );
            }
        }
        return true;
    }

    public function jsonSerialize(): PhpToJsData
    {
        $data = parent::jsonSerialize();
        if ($this->defaultValue) {
            $files = !is_array($this->defaultValue) ? [$this->defaultValue] : $this->defaultValue;
            $defaultValue = [];
            foreach ($files as $file) {
                if (!$file instanceof StorableFile || !$file->id) {
                    continue;
                }
                $defaultValue[] = [
                    'id' => $file->id,
                    'name' => $file->filename,
                    'size' => $file->filesize,
                    'url' => $file->getDownloadUrl(),
                    'deleteUrl' => $this->instantDelete ? $file->getDeleteUrl() : null
                ];
            }
            $data->properties['defaultValue'] = $defaultValue ?: null;
        }
        return $data;
    }


}