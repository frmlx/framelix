<?php

namespace Utils;

use Framelix\Framelix\Date;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\FramelixTests\TestCase;

final class NumberUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame(2, NumberUtils::clamp(3, 0, 2));
        $this->assertSame(2.0, NumberUtils::clamp(3.0, 0, 2));
        $this->assertSame(0, NumberUtils::clamp(-1, 0, 2));
        $this->assertSame(0.0, NumberUtils::toFloat(false));
        $this->assertSame(1.0, NumberUtils::toFloat(true));
        $this->assertSame(1.333, NumberUtils::toFloat(1.333));
        $this->assertSame(1.33, NumberUtils::toFloat(1.333, 2));
        $this->assertSame(1.333, NumberUtils::toFloat("1,333"));
        $this->assertSame(-1.333, NumberUtils::toFloat("-1,333"));
        // this is pretty weird, but objects does work too
        $this->assertSame(2020.0, NumberUtils::toFloat(Date::create("2020-01-01")));
        $this->assertSame("1", NumberUtils::format("1,333"));
        $this->assertSame("10.000", NumberUtils::format("10000,333"));
        $this->assertSame("-10.000", NumberUtils::format("-10000,333"));
        $this->assertSame("10.000,33", NumberUtils::format("10000,333", 2));
        $this->assertSame("10,000.33", NumberUtils::format("10000.333", 2, ".", ","));
        $this->assertSame("+10,000.33", NumberUtils::format("10000.333", 2, ".", ",", true));
        $this->assertSame("-10,000.33", NumberUtils::format("-10000.333", 2, ".", ",", true));
        $this->assertSame("", NumberUtils::format(""));
    }
}
