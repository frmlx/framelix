<?php

namespace Framelix\FramelixDemo\Storable\SystemValue;

use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;
use Framelix\FramelixDemo\Console;

/**
 * Income Category
 * @property string $name
 * @property string|null $info
 */
class IncomeCategory extends SystemValue
{
    public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void
    {
        $meta->addDefaultPropertiesAtStart();

        $meta->createPropertyForStorableArray("summaryKeys", SummaryKey::class);

        $property = $meta->createProperty("name");
        $property->addDefaultField();

        $meta->addDefaultPropertiesAtEnd();
    }

    public function isDeletable(): bool
    {
        return Console::$cleanupMode ?? false;
    }

    public function isEditable(): bool
    {
        return Console::$cleanupMode ?? User::hasRole('admin');
    }

    public function isReadable(): bool
    {
        return Console::$cleanupMode ?? User::hasRole('admin');
    }

    public function getHtmlString(): string
    {
        return $this->name;
    }

    public function getRawTextString(): string
    {
        return $this->name;
    }
}