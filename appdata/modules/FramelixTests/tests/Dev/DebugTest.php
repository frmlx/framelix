<?php

namespace Dev;

use Framelix\Framelix\Dev\Debug;
use Framelix\Framelix\Utils\Buffer;
use Framelix\FramelixTests\TestCase;

use function var_dump;

final class DebugTest extends TestCase
{

    public function tests(): void
    {
        Buffer::start();
        Debug::dump('foo', false);
        $actual = Buffer::get();
        Buffer::start();
        var_dump('foo');
        $expected = Buffer::get();
        $this->assertSame($expected, $actual);
        Buffer::start();
        Debug::dump('foo', true);
        $this->assertMatchesRegularExpression('~console\.log~', Buffer::get());
    }
}
