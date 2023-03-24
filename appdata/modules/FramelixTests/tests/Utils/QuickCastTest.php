<?php

namespace Utils;

use Framelix\Framelix\Utils\QuickCast;
use Framelix\FramelixTests\TestCase;

final class QuickCastTest extends TestCase
{
    public function tests(): void
    {
        $arr = ['0', '222', ['3333']];
        $this->assertSame([0, 222, [3333]], QuickCast::to($arr, 'int'));
        $this->assertSame([0.0, 222.0, [3333.0]], QuickCast::to($arr, 'float'));
        $this->assertSame($arr, QuickCast::to($arr, 'string'));
        $this->assertSame([1], QuickCast::to(1, 'array'));
        $this->assertSame(null, QuickCast::to('', 'array', false, true));
        $this->assertSame(true, QuickCast::to(1, 'bool'));
        $this->assertSame(null, QuickCast::to('', 'bool', false, true));
        $this->assertSame('0,222,3333', QuickCast::to($arr, 'string', false));
    }
}
