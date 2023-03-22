<?php

namespace Utils;

use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\FramelixTests\TestCase;

final class RandomGeneratorTest extends TestCase
{

    public function tests(): void
    {
        $this->assertGreaterThan(12, RandomGenerator::getRandomHtmlId());
        $this->assertGreaterThan(10, RandomGenerator::getRandomInt(11, 12));
    }
}
