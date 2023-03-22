<?php

namespace Framelix\FramelixTests\StorableMeta;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;
use Framelix\FramelixTests\Storable\TestStorable1;

/**
 * TestStorable2
 */
class TestStorable2 extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\FramelixTests\Storable\TestStorable2
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("floatNumber");
        $property->addDefaultField();

        $property = $this->createProperty("intNumberOptional");
        $property->addDefaultField();

        $property = $this->createProperty("boolFlag");
        $property->addDefaultField();

        $property = $this->createProperty("time");
        $property->addDefaultField();

        $property = $this->createProperty("name");
        $property->addDefaultField();
        $property->setLabel("name");

        $property = $this->createProperty("notExisting");
        $property->addDefaultField();
        $property->setVisibility([self::CONTEXT_FORM], false);
        $property->setLabel("notExisting");

        $property = $this->createProperty("longTextOptional");
        $property->addDefaultField();
        $property->setVisibility(null, false);
        $property->setLabel("invisible");

        $property = $this->createProperty("systemValueOptional");
        $property->addDefaultField();
        $property->setLabel("systemValueOptional");

        $property = $this->createProperty("systemValueArrayOptional");
        $property->addDefaultField();

        $property = $this->createProperty("storableFileOptional");
        $property->addDefaultField();

        $property = $this->createProperty("storableFileArrayOptional");
        $property->addDefaultField();

        $property = $this->createProperty("longText");
        $property->addDefaultField();
        $property->setLabel("longText");
        $property->setLabelDescription("longText");
        $property->lazySearchConditionColumns->addColumn('longText', 'longText', 'string');

        $property = $this->createProperty("selfReferenceOptional");
        $property->addDefaultField();

        $property = $this->createProperty("otherReferenceOptional");
        $property->addDefaultField();

        $property = $this->createProperty("otherReferenceArrayOptional");
        $property->addDefaultField();

        $property = $this->createProperty("dateTime");
        $property->addDefaultField();

        $property = $this->createProperty("date");
        $property->addDefaultField();

        $property = $this->createProperty("typedFloatArray");
        $property->field = new Select();
        $property->field->multiple = true;
        $property->field->addOption('123', '123');

        $property = $this->createProperty("typedStringArray[foo]");
        $property->field = new Select();
        $property->field->addOption('test', 'test');

        // to test coverage for invalid property type
        $storable = $this->storable;
        /** @phpstan-ignore-next-line */
        $this->storable = new TestStorable1();
        $this->addTimestampProperty();
        $this->storable = $storable;

        $this->addDefaultPropertiesAtEnd();
    }
}