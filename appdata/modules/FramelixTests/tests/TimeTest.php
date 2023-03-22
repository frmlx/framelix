<?php

use Framelix\Framelix\Time;
use Framelix\FramelixTests\TestCase;

final class TimeTest extends TestCase
{
    public function tests(): void
    {
        $this->callStorableInterfaceMethods(Time::class);
        $this->assertSame(12.55, Time::timeStringToHours('12:33'));
        $this->assertSame(12.5592, Time::timeStringToHours('12:33:33'));
        $this->assertSame(12.5667, Time::timeStringToHours('12:34'));
        $this->assertSame(12.5675, Time::timeStringToHours('12:34:03'));
        $this->assertSame('12:33', Time::hoursToTimeString(12.55));
        $this->assertSame('12:34', Time::hoursToTimeString(12.5667));
        $this->assertSame('12:33:33', Time::hoursToTimeString(12.5592, true));
        $this->assertSame('12:34:03', Time::hoursToTimeString(12.5675, true));
        $this->assertSame(45180, Time::timeStringToSeconds('12:33'));
        $this->assertSame(45181, Time::timeStringToSeconds('12:33:01'));
        $this->assertSame(45239, Time::timeStringToSeconds('12:33:59'));
        $this->assertSame(45239 + 4, Time::timeStringToSeconds('12:34:03'));
        $this->assertSame(0, Time::timeStringToSeconds(null));
        $time = Time::create('12:34:03');
        $this->assertSame('45243', (string)$time);
        $this->assertSame(45243, $time->getSortableValue());
        $this->assertSame('12:34', $time->getHtmlString());
        $this->assertSame('12:34', $time->jsonSerialize());
        $this->assertSame('12:34', $time->getRawTextString());
    }
}
