<?php

namespace Utils;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\CryptoUtils;
use Framelix\FramelixTests\TestCase;

final class CryptoUtilsTest extends TestCase
{

    public function tests(): void
    {
        Config::$salts['default'] = 'UNITTESTSALT';
        $hash = CryptoUtils::hash(["foo", "bar"]);
        $this->assertSame('74360d47fb5e9cea2a51200cc5762a69', $hash);
        $this->assertTrue(CryptoUtils::compareHash(["foo", "bar"], $hash));
    }
}
