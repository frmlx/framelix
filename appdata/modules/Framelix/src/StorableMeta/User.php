<?php

namespace Framelix\Framelix\StorableMeta;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;
use Framelix\Framelix\View;

/**
 * User
 */
class User extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Framelix\Storable\User
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->tableDefault->addColumnFlag('simulateUser', Table::COLUMNFLAG_REMOVE_IF_EMPTY);
        $property = $this->createProperty("simulateUser");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->setLabel('');
        $property->valueCallable = function () {
            return TableCell::create('<framelix-button href="'.View::getUrl(View\Backend\User\Index::class)->setParameter(
                'simulateUser',
                $this->storable
            ).'" icon="739" title="__framelix_simulateuser__"></framelix-button>');
        };

        $this->addDefaultPropertiesAtStart();

        $field = new Email();
        $property = $this->createProperty("email");
        $property->field = $field;
        $property->setLabel("__framelix_email__");

        $field = new Toggle();
        $property = $this->createProperty("flagLocked");
        $property->field = $field;
        $property->setLabel("__framelix_user_flag_locked__");
        $property->setLabelDescription("__framelix_user_flag_locked_desc__");

        $this->addDefaultPropertiesAtEnd();
    }
}