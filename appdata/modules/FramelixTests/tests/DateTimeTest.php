<?php

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\FramelixTests\TestCase;

final class DateTimeTest extends TestCase
{
    public function tests(): void
    {
        $this->callStorableInterfaceMethods(DateTime::class);
        $obj = DateTime::create('2000-01-01 12:00:00');
        $obj2 = DateTime::create('2000-01-01 13:00:00');
        // database is always UTC
        $this->assertSame(gmdate('Y-m-d H:i:s', $obj->getTimestamp()), $obj->getDbValue());
        $this->assertSame(gmdate('Y-m-d H:i:s', $obj->getTimestamp()), (string)$obj);
        $this->assertSame(gmdate('"Y-m-d H:i:s"', $obj->getTimestamp()), json_encode($obj));
        $this->assertSame(20000101120000, $obj->getSortableValue());
        $this->assertSame('01.01.2000 12:00:00', $obj->getHtmlString());
        $this->assertSame('01.01.2000 12:00:00', $obj->getRawTextString());
        $this->assertSame('01.01.2000', DateTime::anyToFormat('2000-01-01 12:00:00'));
        $this->assertSame('01.01.2000', DateTime::anyToFormat($obj));
        $this->assertSame('01.01.2000', DateTime::anyToFormat(Date::create('01.01.2000')));
        $this->assertSame(null, DateTime::anyToFormat('&'));
        $this->assertSame(null, DateTime::anyToFormat(null));
        $this->assertSame(null, DateTime::anyToFormat(' '));
        $this->assertSame(null, DateTime::anyToFormat(new stdClass()));
        $this->assertNotSame($obj, DateTime::create($obj));
        $this->assertInstanceOf(DateTime::class, DateTime::create(Date::create('now')));
        $this->assertSame(20000101120000, $obj->clone()->getSortableValue());
        $this->assertSame(20000101, $obj->cloneToDate()->getSortableValue());
        $this->assertSame('01.01.2000', DateTime::anyToFormat($obj->getTimestamp()));
        $this->assertSame($obj2, DateTime::max($obj, $obj2));
        $this->assertSame($obj, DateTime::min($obj, $obj2));
        $this->assertSame(1, $obj->getQuarterStartMonth()->getMonth());
        $this->assertSame(3, $obj->getQuarterEndMonth()->getMonth());
        $this->assertSame(1, $obj->getQuarter());
        $this->assertSame(1, $obj->getDayOfMonth());
        $this->assertSame(6, $obj->getDayOfWeek());
        $this->assertSame(12, $obj->getHours());
        $this->assertSame(0, $obj->getMinutes());
        $this->assertSame(0, $obj->getSeconds());
        $this->assertIsString($obj->getDayName());
        $this->assertIsString($obj->getMonthName());
        $this->assertIsString($obj->getMonthNameAndYear());

        $this->assertSame(1999, $obj->setYear(1999)->getYear());
        $this->assertSame(12, $obj->setMonth(12)->getMonth());
        $this->assertSame(15, $obj->setDayOfMonth(15)->getDayOfMonth());
        $this->assertSame(20, $obj->setHours(20)->getHours());
        $this->assertSame(20, $obj->setMinutes(20)->getMinutes());
        $this->assertSame(20, $obj->setSeconds(20)->getSeconds());
        $this->assertSame('1 second', $obj->getRelativeTimeUnits($obj->clone()->modify("+ 1 second"), 'en'));
        $this->assertSame('1 minute', $obj->getRelativeTimeUnits($obj->clone()->modify("+ 1 minute"), 'en'));
        $this->assertSame('401 hours', $obj->getRelativeTimeUnits($obj2, 'en'));
    }
}
