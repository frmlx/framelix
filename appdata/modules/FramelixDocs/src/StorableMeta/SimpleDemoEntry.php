<?php

namespace Framelix\FramelixDocs\StorableMeta;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Search;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

class SimpleDemoEntry extends StorableMeta
{
    /**
     * @var \Framelix\FramelixDocs\Storable\SimpleDemoEntry
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->tableDefault->storableDeletable = false;
        $this->addDefaultPropertiesAtStart();

        $field = new Email();
        $property = $this->createProperty("email");
        $property->field = $field;
        $property->setLabel("Your E-Mail");

        $property = $this->createProperty("name");
        $property->addDefaultField();
        $property->setLabel("Your Name");

        $property = $this->createProperty("muchoMachoText");
        $property->addDefaultField();
        $property->setLabel("You can write whatever you want");

        $property = $this->createProperty("logins");
        $property->addDefaultField();
        $property->setLabel("Give as a number of logins");

        $property = $this->createProperty("lastLogin");
        $property->addDefaultField();
        $property->setLabel("When was your last login");

        $property = $this->createProperty("flagActive");
        $property->addDefaultField();
        $property->setLabel("Is it enabled?");

        $field = new Search();
        $field->setSearchWithStorable(\Framelix\FramelixDocs\Storable\SimpleDemoEntry::class, ['name']);
        $property = $this->createProperty("referenceEntry");
        $property->field = $field;
        $property->setLabel("Another Entry References");
        $property->setLabelDescription('Search by Name');

        $this->addDefaultPropertiesAtEnd();
    }
}