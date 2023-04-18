<?php

namespace Framelix\FramelixDemo\StorableMeta;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

class InvoicePosition extends StorableMeta
{
    /**
     * @var \Framelix\FramelixDemo\Storable\InvoicePosition
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->tableDefault->initialSort = ["+sort"];

        $this->addDefaultPropertiesAtStart();

        $property = $this->createProperty("count");
        $property->field = new Number();

        $property = $this->createProperty("comment");
        $property->field = new Textarea();

        $property = $this->createProperty("netSingle");
        $property->field = new Number();
        $property->field->decimals = 2;

        $this->addDefaultPropertiesAtEnd();
    }
}