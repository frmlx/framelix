<?php

namespace Framelix\FramelixDocs\Storable;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\StorableExtended;

/**
 * Simple Demo Entry Storable
 * @property string|null $clientId
 * @property string $email
 * @property string|null $name
 * @property string|null $muchoMachoText
 * @property int|null $logins
 * @property DateTime|null $lastLogin
 * @property bool $flagActive
 * @property mixed|null $anyJsonData
 * @property SimpleDemoEntry|null $referenceEntry
 * @property SimpleDemoEntry[]|null $arrayOfReferences
 */
class SimpleDemoEntry extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['muchoMachoText']->databaseType = 'longtext';
        $selfStorableSchema->properties['muchoMachoText']->lazyFetch = true;
        $selfStorableSchema->addIndex('clientId', 'index');
    }
}