<?php

namespace Utils;

use Framelix\Framelix\Utils\ColorUtils;
use Framelix\FramelixTests\TestCase;

final class ColorUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame('#00ffff', ColorUtils::invertColor("#ff0000"));
        $this->assertSame('#ffffff', ColorUtils::invertColor("#ff0000", true));
        $this->assertSame('#000000', ColorUtils::invertColor("#bbbbbb", true));
        $this->assertSame([20, 189, 189], ColorUtils::hslToRgb(180, 0.81, 0.41));
        $this->assertSame([189, 48, 20], ColorUtils::hslToRgb(10, 0.81, 0.41));
        $this->assertSame([48, 189, 20], ColorUtils::hslToRgb(110, 0.81, 0.41));
        $this->assertSame([20, 189, 161], ColorUtils::hslToRgb(170, 0.81, 0.41));
        $this->assertSame([161, 20, 189], ColorUtils::hslToRgb(290, 0.81, 0.41));
        $this->assertSame([189, 20, 105], ColorUtils::hslToRgb(330, 0.81, 0.41));
        $this->assertSame([180, 0.126, 0.704], ColorUtils::rgbToHsl(170, 189, 189));
        $this->assertSame([0, 0.0, 0.078], ColorUtils::rgbToHsl(20, 20, 20));
        $this->assertSame([0, 0.818, 0.431], ColorUtils::rgbToHsl(200, 20, 20));
        $this->assertSame([357, 0.818, 0.431], ColorUtils::rgbToHsl(200, 20, 30));
        $this->assertSame([120, 0.818, 0.431], ColorUtils::rgbToHsl(20, 200, 20));
        $this->assertSame([240, 0.818, 0.431], ColorUtils::rgbToHsl(20, 20, 200));
        $this->assertSame("#14bdbd", ColorUtils::rgbToHex(20, 189, 189));
    }
}
