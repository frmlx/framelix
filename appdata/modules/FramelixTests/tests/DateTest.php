<?php

use Framelix\Framelix\Date;
use Framelix\FramelixTests\TestCase;

final class DateTest extends TestCase
{
    public function tests(): void
    {
        $this->callStorableInterfaceMethods(Date::class);
        $objDt = \Framelix\Framelix\DateTime::create('2000-01-01 12:00:00');
        $obj = Date::create('2000-01-01 12:00:00');
        $obj2 = Date::create('2000-02-01 13:00:00');
        $obj3 = Date::create('2000-01-01 20:00:00');
        $this->assertSame(gmdate('Y-m-d', $objDt->getTimestamp()), $obj->getDbValue());
        $this->assertSame(gmdate('Y-m-d', $objDt->getTimestamp()), (string)$obj);
        $this->assertSame(gmdate('"Y-m-d"', $objDt->getTimestamp()), json_encode($obj));
        $this->assertSame(20000101, $obj->getSortableValue());
        $this->assertSame(
            '<framelix-time datetime="2000-01-01T00:00:00+01:00" format="DD.MM.YYYY"></framelix-time>',
            $obj->getHtmlString()
        );
        $this->assertSame(
            '<framelix-time datetime="2000-01-01T00:00:00+01:00" format="DD.MM.YYYY HH:mm:ss"></framelix-time>',
            $obj->cloneToDateTime()->getHtmlString()
        );
        $this->assertSame('01.01.2000', $obj->getRawTextString());
        $this->assertSame($obj2, Date::max($obj, $obj2));
        $this->assertSame($obj, Date::min($obj, $obj2));
        $this->assertSame("<", Date::compare($obj, $obj2));
        $this->assertSame(">", Date::compare($obj2, $obj));
        $this->assertSame("=", Date::compare($obj3, $obj));
        $this->assertSame(
            '["2001-02-03","2001-02-04","2001-02-05"]',
            json_encode(Date::rangeDays("2001-02-03", "2001-02-05"))
        );
        $this->assertSame('[]', json_encode(Date::rangeDays("2001-02-03", null)));
        $this->assertSame(
            '["2001-02-01","2001-03-01","2001-04-01"]',
            json_encode(Date::rangeMonth("2001-02-03", "2001-04-05"))
        );
        $this->assertSame('[]', json_encode(Date::rangeMonth("2001-02-03", null)));

        $this->assertExceptionOnCall(function () use ($obj) {
            $obj = clone $obj;
        });
    }
}
