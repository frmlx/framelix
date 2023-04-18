<?php

namespace Framelix\FramelixDemo\Storable\SystemValue;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;
use Framelix\FramelixDemo\Console;

/**
 * Outgoing Category
 * @property string $name
 * @property string|null $info
 * @property int $operationalSharePercent
 */
class OutgoingCategory extends SystemValue
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['operationalSharePercent']->length = 3;
    }

    public static function setupStorableMeta(\Framelix\Framelix\StorableMeta\SystemValue $meta): void
    {
        $meta->addDefaultPropertiesAtStart();

        $meta->createPropertyForStorableArray("summaryKeys", SummaryKey::class);

        $property = $meta->createProperty("name");
        $property->addDefaultField();

        $property = $meta->createProperty("operationalSharePercent");
        $property->addDefaultField();
        /** @var Number $field */
        $field = $property->field;
        $field->min = 0;
        $field->max = 100;

        $meta->addDefaultPropertiesAtEnd();
    }

    public function isDeletable(): bool
    {
        return Console::$cleanupMode ?? false;
    }

    public function isEditable(): bool
    {
        return User::hasRole('admin');
    }

    public function isReadable(): bool
    {
        return User::hasRole('admin');
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