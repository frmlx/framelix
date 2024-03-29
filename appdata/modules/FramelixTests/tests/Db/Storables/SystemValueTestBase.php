<?php

namespace Db\Storables;

use Framelix\FramelixTests\Storable\TestStorableSystemValue;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class SystemValueTestBase extends TestCaseDbTypes
{

    public function test(): void
    {
        $this->setupDatabase(true);

        $storable1 = new TestStorableSystemValue();
        $storable1->name = "name2";
        $storable1->flagActive = true;
        $storable1->sort = 1;
        $storable1->store();
        $this->assertSame(1, $storable1->id);

        $storable2 = new TestStorableSystemValue();
        $storable2->name = "name1";
        $storable2->flagActive = true;
        $storable2->sort = 2;
        $storable2->store();
        $this->assertTrue($storable2->isReadable());
        $this->assertTrue($storable2->isEditable());
        $this->assertTrue($storable2->isDeletable());

        $storable3 = new TestStorableSystemValue();
        $storable3->name = "name4";
        $storable3->flagActive = false;
        $storable3->sort = 3;
        $storable3->store();

        $this->assertSame(
            [$storable1->id => $storable1, $storable2->id => $storable2, $storable3->id => $storable3],
            TestStorableSystemValue::getEntries($storable3)
        );

        $this->assertSame(
            [$storable1->id => $storable1, $storable2->id => $storable2, $storable3->id => $storable3],
            TestStorableSystemValue::getEntries(null, false)
        );

        $this->assertSame(
            [$storable1->id => $storable1, $storable2->id => $storable2],
            TestStorableSystemValue::getEntries()
        );

        $this->assertSame(
            [],
            TestStorableSystemValue::getEntries(additionalCondition: "0")
        );

        $this->assertStorableDefaultGetters($storable3);

        $meta = new \Framelix\Framelix\StorableMeta\SystemValue($storable3);
        TestStorableSystemValue::setupStorableMeta($meta);
    }

}