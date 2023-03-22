<?php

namespace Framelix\FramelixTests\StorableMeta;

use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

class TestStorableSystemValue extends StorableMeta\SystemValue
{
    /**
     * @var \Framelix\FramelixTests\Storable\TestStorableSystemValue
     */
    public Storable $storable;

    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();
        $property = $this->createProperty("name");
        $property->addDefaultField();
        $this->addDefaultPropertiesAtEnd();
    }
}