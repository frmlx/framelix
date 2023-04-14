<?php

namespace Framelix\FramelixTests\tests\Utils;

use Framelix\Framelix\Utils\Mutex;
use Framelix\FramelixTests\TestCase;

use function sleep;

final class MutexTest extends TestCase
{

    public function tests(): void
    {
        Mutex::release("test");
        $this->assertFalse(!!Mutex::isLocked("test"));
        $this->assertSame(0, Mutex::isLocked("test"));
        $this->assertSame(0, Mutex::isLocked("test", 2));

        Mutex::create("test");
        $this->assertTrue(!!Mutex::isLocked("test"));
        $this->assertSame(-1, Mutex::isLocked("test"));
        $this->assertSame(2, Mutex::isLocked("test", 2));
        sleep(1);
        $this->assertLessThan(2, Mutex::isLocked("test", 2));
        Mutex::release("test");
        $this->assertFalse(!!Mutex::isLocked("test"));
    }
}
