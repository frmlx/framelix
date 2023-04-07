<?php

namespace Framelix\FramelixTests\Storable;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Time;

use function str_repeat;

/**
 * TestStorable2
 * @property string $name
 * @property string $longText
 * @property string|null $longTextLazy
 * @property string|null $longTextOptional
 * @property int $intNumber
 * @property int|null $intNumberOptional
 * @property float $floatNumber
 * @property float|null $floatNumberOptional
 * @property bool $boolFlag
 * @property bool|null $boolFlagOptional
 * @property mixed $jsonData
 * @property mixed|null $jsonDataOptional
 * @property Time|null $time
 * @property TestStorable2|null $selfReferenceOptional
 * @property TestStorableSystemValue|null $systemValueOptional
 * @property TestStorableFile|null $storableFileOptional
 * @property TestStorable1|null $otherReferenceOptional
 * @property DateTime $dateTime
 * @property DateTime|null $dateTimeOptional
 * @property Date $date
 * @property DateTime|null $dateOptional
 * @property int|null $sort
 */
class TestStorable2 extends StorableExtended
{
    public static function getNewTestInstance(): self
    {
        $storable = new TestStorable2();
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->longTextLazy = str_repeat("foo", 1000);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime("2000-01-01 12:23:44");
        $storable->date = Date::create("2000-01-01");
        $storable->time = Time::create("12:00:01");
        $storable->sort = 0;
        $storable->updateTime = DateTime::create('now - 10 seconds');
        $storable->store();
        return $storable;
    }

    /**
     * Setup self storable meta
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->connectionId = "test";
        $storableSchemaProperty = $selfStorableSchema->properties['floatNumberOptional'];
        $storableSchemaProperty->dbComment = "Some comment";
        $storableSchemaProperty->length = 11;
        $storableSchemaProperty->decimals = 3;
        $storableSchemaProperty = $selfStorableSchema->properties['longText'];
        $storableSchemaProperty->databaseType = "longtext";
        $storableSchemaProperty->length = null;
        $storableSchemaProperty = $selfStorableSchema->properties['longTextLazy'];
        $storableSchemaProperty->databaseType = "longtext";
        $storableSchemaProperty->length = null;
        $storableSchemaProperty->lazyFetch = true;
        $storableSchemaProperty = $selfStorableSchema->properties['longTextOptional'];
        $storableSchemaProperty->databaseType = "longtext";
        $storableSchemaProperty->length = null;
        $storableSchemaProperty = $selfStorableSchema->properties['date'];
        $storableSchemaProperty->databaseType = "date";
        $storableSchemaProperty = $selfStorableSchema->properties['dateOptional'];
        $storableSchemaProperty->databaseType = "date";
        $selfStorableSchema->addIndex('longText', 'fulltext');
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