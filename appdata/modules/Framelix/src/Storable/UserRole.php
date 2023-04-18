<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;

/**
 * @property User $user
 * @property string $role
 */
class UserRole extends StorableExtended
{
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->addIndex('role', 'index');
    }
}