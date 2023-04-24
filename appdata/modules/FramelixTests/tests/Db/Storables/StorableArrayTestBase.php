<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\StorableArray;
use Framelix\FramelixTests\Storable\TestStorableSystemValue;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class StorableArrayTestBase extends TestCaseDbTypes
{
    public function test(): void
    {
        $this->setupDatabase(true);

        $parent = new TestStorableSystemValue();
        $parent->name = "name1";
        $parent->flagActive = true;
        $parent->sort = 2;
        $parent->store();

        $parent2 = new TestStorableSystemValue();
        $parent2->name = "name1";
        $parent2->flagActive = true;
        $parent2->sort = 2;
        $parent2->store();

        $testValue = ['foo'];
        $testValue2 = ['foo', ['12302']];

        StorableArray::setValue($parent, 'test', $testValue);
        StorableArray::setValue($parent2, 'test', $testValue);
        StorableArray::setValue($parent, 'test2', $testValue2);
        StorableArray::setValue($parent2, 'test2', $testValue2);
        $this->assertSame($testValue, StorableArray::getValue($parent, 'test'));
        $this->assertSame($testValue, StorableArray::getValue($parent, 'test'));
        $this->assertSame($testValue, StorableArray::getValue($parent, 'test', true));
        $this->assertSame($testValue, StorableArray::getValue($parent2, 'test'));
        $this->assertSame($testValue2, StorableArray::getValue($parent, 'test2'));
        $this->assertSame($testValue2, StorableArray::getValue($parent2, 'test2'));

        StorableArray::deleteValue($parent, 'test');
        $this->assertNull(StorableArray::getValue($parent, 'test'));
        $this->assertSame($testValue2, StorableArray::getValue($parent, 'test2'));

        StorableArray::deleteValues($parent2);
        $this->assertNull(StorableArray::getValue($parent2, 'test'));
        $this->assertNull(StorableArray::getValue($parent2, 'test2'));
    }
}