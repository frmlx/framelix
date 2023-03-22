<?php

namespace Utils;

use Framelix\Framelix\Utils\VersionUtils;
use Framelix\FramelixTests\TestCase;

final class VersionUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame([
            'major' => 0,
            'minor' => 1,
            'patch' => 2,
            'devBranch' => null,
            'devVersion' => null
        ], VersionUtils::splitVersionString("0.1.2"));
        $this->assertSame([
            'major' => 0,
            'minor' => 1,
            'patch' => 2,
            'devBranch' => 'RC',
            'devVersion' => 1
        ], VersionUtils::splitVersionString("0.1.2RC1"));
    }
}
