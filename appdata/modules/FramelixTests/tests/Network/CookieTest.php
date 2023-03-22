<?php

namespace Network;

use Framelix\Framelix\Network\Cookie;
use Framelix\FramelixTests\TestCase;

final class CookieTest extends TestCase
{

    public function tests(): void
    {
        $this->assertNull(Cookie::get('foo'));
        Cookie::set('foo', '123456');
        $this->assertSame('123456', Cookie::get('foo'));
        Cookie::set('foo', '123456');
        $this->assertSame('123456', Cookie::get('foo'));
        Cookie::set('foo', null);
        $this->assertNull(Cookie::get('foo'));

        // test signature by changed the signature hash
        Cookie::set('foo', '123456');
        $this->assertSame('123456', Cookie::get('foo'));
        $_COOKIE['foo__s'] .= "1";
        $this->assertNull(Cookie::get('foo'));
    }
}
