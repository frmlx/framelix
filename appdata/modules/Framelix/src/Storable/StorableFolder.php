<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;

/**
 * A storable virtual folder
 * @property StorableFolder|null $parent
 * @property string $name
 */
class StorableFolder extends StorableExtended
{
    public function getMediaBrowserThumbnailHtml(): string
    {
        return '<div class="framelix-mediabrowser-thumbnail"
                                     style="background-image: url(' . Url::getUrlToPublicFile(
                __DIR__ . "/../../public/img/mediabrowser_folder.svg"
            ) . ')"></div>';
    }

    /**
     * Get all childs, including folders
     * @param bool $recursive
     * @return StorableFile[]|StorableFolder[]
     */
    public function getChilds(bool $recursive): array
    {
        $folders = StorableFolder::getByCondition('parent = {0}', [$this]);
        $files = StorableFile::getByCondition('storableFolder = {0}', [$this], withChilds: true);
        $arr = ArrayUtils::merge(
            $folders,
            $files
        );
        if ($recursive) {
            foreach ($folders as $folder) {
                $arr = ArrayUtils::merge(
                    $arr,
                    $folder->getChilds($recursive)
                );
            }
        }
        return $arr;
    }

    /**
     * Get full name to this folder
     * @return string
     */
    public function getFullName(): string
    {
        $str = [$this->name];
        $parent = $this;
        while ($parent = $parent->parent) {
            $str[] = $parent->name;
        }
        return implode(" / ", array_reverse($str));
    }

    public function delete(bool $force = false): void
    {
        $id = $this->id;
        parent::delete($force);
        self::deleteMultiple(StorableFile::getByCondition('storableFolder = {0}', [$id]));
        self::deleteMultiple(self::getByCondition('parent = {0}', [$id]));
    }
}