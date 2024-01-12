<?php

namespace Framelix\FramelixDocs\Storable;

use Framelix\Framelix\Storable\StorableFile;

class SimpleDemoFile extends StorableFile
{

    public static function getUserdataFolderPath(bool $public = false): string
    {
        return parent::getUserdataFolderPath(true);
    }

}