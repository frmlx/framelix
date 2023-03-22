<?php

namespace Network;

use Framelix\Framelix\Network\Session;
use Framelix\FramelixTests\TestCase;

final class SessionTest extends TestCase
{

    public function tests(): void
    {
        $this->assertNull(Session::get('foo'));
        Session::set('foo', '123456');
        $this->assertSame('123456', Session::get('foo'));
        Session::destroy();
        $this->assertNull(Session::get('foo'));
        Session::set('foo', '123456');
        $this->assertSame('123456', Session::get('foo'));
        Session::set('foo', null);
        $this->assertNull(Session::get('foo'));
    }
}
