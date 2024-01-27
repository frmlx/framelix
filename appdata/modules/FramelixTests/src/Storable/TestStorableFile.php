<?php

namespace Framelix\FramelixTests\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableFile;

/**
 * TestStorableFile
 */
class TestStorableFile extends StorableFile
{
    /**
     * Setup self storable meta
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->connectionId = "test";
    }

    public static function getUserdataFolderPath(bool $public = false): string
    {
        return parent::getUserdataFolderPath(true);
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return true;
    }
}