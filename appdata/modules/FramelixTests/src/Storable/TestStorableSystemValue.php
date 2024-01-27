<?php

namespace Framelix\FramelixTests\Storable;

use Framelix\Framelix\Storable\SystemValue;

/**
 * TestStorableSystemValue
 * @property  string $name
 */
class TestStorableSystemValue extends SystemValue
{

    public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void
    {
    }

    public function isDeletable(): bool
    {
        // just to extend coverage
        parent::isDeletable();
        return true;
    }

    public function isEditable(): bool
    {
        // just to extend coverage
        parent::isEditable();
        return true;
    }

    public function isReadable(): bool
    {
        // just to extend coverage
        parent::isReadable();
        return true;
    }
}