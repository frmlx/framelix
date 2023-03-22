<?php

namespace Framelix\FramelixTests\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

/**
 * TestStorablePrefetch
 * @property TestStorable2 $otherReference
 * @property TestStorable2 $otherReferenceNoPrefetch
 * @property TestStorable2 $otherReferenceReducedPrefetch
 * @property TestStorable2[]|null $otherReferenceArrayDefaultPrefetch
 * @property TestStorable2[]|null $otherReferenceArrayNoPrefetch
 * @property TestStorable2[]|null $otherReferenceArrayReducedPrefetch
 * @property mixed|null $requiredIds
 */
class TestStorablePrefetch extends StorableExtended
{
    /**
     * Setup self storable meta
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->connectionId = "test";
        $storableSchemaProperty = $selfStorableSchema->properties['otherReferenceNoPrefetch'];
        $storableSchemaProperty->prefetchReferenceStorable = false;
        $storableSchemaProperty = $selfStorableSchema->properties['otherReferenceArrayNoPrefetch'];
        $storableSchemaProperty->prefetchReferenceStorable = false;
        $storableSchemaProperty = $selfStorableSchema->properties['otherReferenceReducedPrefetch'];
        $storableSchemaProperty->prefetchLimit = 10;
        $storableSchemaProperty = $selfStorableSchema->properties['otherReferenceArrayReducedPrefetch'];
        $storableSchemaProperty->prefetchLimit = 7;
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