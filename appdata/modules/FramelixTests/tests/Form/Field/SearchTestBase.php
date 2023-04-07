<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Search;
use Framelix\FramelixTests\Storable\TestStorable1;
use Framelix\FramelixTests\StorableMeta\TestStorable2;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class SearchTestBase extends TestCaseDbTypes
{
    public function tests(): void
    {
        $this->setupDatabase(true);
        $field = new Search();
        $field->name = $field::class;
        $field->required = true;

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertTrue($field->validate());
        $this->assertSame("#aaaaaa", $field->getSubmittedValue());

        $this->setSimulatedGetData(
            ['storableClass' => \Framelix\FramelixTests\Storable\TestStorable2::class, 'properties' => ['name']]
        );
        $field->setSearchMethod(Search::class, 'search', ['query' => '123']);
        $field->defaultValue = new \Framelix\FramelixTests\Storable\TestStorable2();
        $this->assertTrue(isset($field->jsonSerialize()->properties['signedUrlSearch']));

        $storableMeta = new TestStorable2(new \Framelix\FramelixTests\Storable\TestStorable2());
        $parameters = $storableMeta->jsonSerialize();
        $parameters['sort'] = null;
        $parameters['limit'] = null;
        $parameters['query'] = '123';
        $this->setSimulatedUrl("http://localhost");
        $this->setSimulatedGetData($parameters);
        $field->setSearchMethod(Search::class, 'quicksearch', $parameters);
        $field->defaultValue = new \Framelix\FramelixTests\Storable\TestStorable2();
        $this->assertTrue(isset($field->jsonSerialize()->properties['signedUrlSearch']));

        $field->setSearchWithStorable(TestStorable1::class, ['name']);
        $field->defaultValue = new \Framelix\FramelixTests\Storable\TestStorable2();
        $this->assertTrue(isset($field->jsonSerialize()->properties['signedUrlSearch']));

        $field->setSearchWithStorableMetaQuickSearch(
            \Framelix\FramelixTests\Storable\TestStorable2::class,
            TestStorable2::class
        );
        $field->defaultValue = new \Framelix\FramelixTests\Storable\TestStorable2();
        $this->assertTrue(isset($field->jsonSerialize()->properties['signedUrlSearch']));
    }
}
