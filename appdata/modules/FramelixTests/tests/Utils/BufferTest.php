<?php

namespace Utils;

use Framelix\Framelix\Utils\Buffer;
use PHPUnit\Framework\TestCase;

final class BufferTest extends TestCase
{

    public function tests(): void
    {
        Buffer::start();
        echo 123;
        Buffer::clear();
        $this->assertSame('', Buffer::get());

        Buffer::start();
        echo 123;
        Buffer::start();
        echo 123;
        $this->assertSame('123123', Buffer::getAll());

        Buffer::start();
        echo 123;
        $this->assertSame('123', Buffer::get());

        // empty buffer
        $this->assertSame('', Buffer::get());
    }
}
