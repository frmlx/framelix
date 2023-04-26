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
        return true;
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return true;
    }
}