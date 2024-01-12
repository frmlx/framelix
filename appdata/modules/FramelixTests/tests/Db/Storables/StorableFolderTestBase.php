<?php

namespace Db\Storables;

use Framelix\Framelix\Storable\StorableFolder;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class StorableFolderTestBase extends TestCaseDbTypes
{
    public function test(): void
    {
        $this->setupDatabase();

        $folder1 = new StorableFolder();
        $folder1->name = "test";
        $folder1->store();

        $folder2 = new StorableFolder();
        $folder2->name = "test-sub";
        $folder2->parent = $folder1;
        $folder2->store();

        $folder3 = new StorableFolder();
        $folder3->name = "test-sub-sub";
        $folder3->parent = $folder2;
        $folder3->store();

        $this->assertSame('test / test-sub / test-sub-sub', $folder3->getFullName());

        $this->assertCount(2, $folder1->getChilds(true));

        $folder1->delete();

        $this->assertNull($folder1->id);
        $this->assertNull($folder2->id);
        $this->assertNull($folder3->id);
    }
}