<?php

namespace Utils;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\TestCase;
use stdClass;

use const SORT_ASC;
use const SORT_DESC;

final class ArrayUtilsTest extends TestCase
{

    public function tests(): void
    {
        $objArray = [
            new stdClass()
        ];
        $objArray[0]->foo = "bar";
        $this->assertSame(
            $objArray[0]->foo,
            ArrayUtils::getValue($objArray, '0[foo]')
        );
        ArrayUtils::setValue($objArray, "2[deeper]", "nope");
        $this->assertSame(
            "nope",
            ArrayUtils::getValue($objArray, '2[deeper]')
        );
        $array = [
            "foo1" => DateTime::create("2016-01-01 00:00:02"), // 1
            "foo2" => "2016-01-01 00:00:01", // 0
            "foo3" => "2017-01-01 00:00:01", // 2
            "foo6" => "2019-01-01 00:00:01", // 5
            "foo7" => "2018-01-01 00:00:01", // 3
            "foo8" => DateTime::create("2018-01-02 00:00:01") // 4
        ];
        $this->assertEqualArray(
            ArrayUtils::merge($array, $objArray),
            '{"foo1":"2015-12-31 23:00:02","foo2":"2016-01-01 00:00:01","foo3":"2017-01-01 00:00:01","foo6":"2019-01-01 00:00:01","foo7":"2018-01-01 00:00:01","foo8":"2018-01-01 23:00:01","0":{"foo":"bar"},"2":{"deeper":"nope"}}'
        );
        $this->assertSame(
            [],
            ArrayUtils::map(null, "getTimestamp")
        );
        $this->assertEqualArray(
            ArrayUtils::map($array, "getTimestamp"),
            '[1451602802,null,null,null,null,1514847601]'
        );
        $this->assertEqualArray(
            ArrayUtils::map($array, "getTimestamp", "getTimestamp"),
            '{"1451602802":1451602802,"1514847601":1514847601}'
        );
        $this->assertEqualArray(
            ArrayUtils::map($array, "clone[methodNotExist]"),
            '[null,null,null,null,null,null]'
        );
        $this->assertEqualArray(
            ArrayUtils::map($array, "methodNotExist"),
            '[null,null,null,null,null,null]'
        );
        $this->assertSame(
            $array["foo1"],
            ArrayUtils::getValue($array, 'foo1')
        );
        $this->assertSame(
            1451602802,
            ArrayUtils::getValue($array, 'foo1[getTimestamp]')
        );
        ArrayUtils::sort($array, null, [SORT_ASC]);
        $this->stringifyArray($array);
        $this->assertEqualArray(
            $array,
            '{"foo2":"2016-01-01 00:00:01","foo1":"2016-01-01 00:00:02","foo3":"2017-01-01 00:00:01","foo7":"2018-01-01 00:00:01","foo8":"2018-01-02 00:00:01","foo6":"2019-01-01 00:00:01"}'
        );

        ArrayUtils::sort($array, null, [SORT_DESC]);
        $this->stringifyArray($array);
        $this->assertEqualArray(
            $array,
            '{"foo6":"2019-01-01 00:00:01","foo8":"2018-01-02 00:00:01","foo7":"2018-01-01 00:00:01","foo3":"2017-01-01 00:00:01","foo1":"2016-01-01 00:00:02","foo2":"2016-01-01 00:00:01"}'
        );

        $array = [
            "foo1" => DateTime::create("2016-02-01 00:00:02"), // 0
            "foo6" => DateTime::create("2019-05-01 00:00:01"), // 3
            "foo2" => DateTime::create("2016-03-01 00:00:01"), // 1
            "foo7" => DateTime::create("2018-06-01 00:00:01"), // 4
            "foo8" => DateTime::create("2018-07-02 00:00:01"), // 5
            "foo3" => DateTime::create("2017-04-01 00:00:01"), // 2
        ];
        ArrayUtils::sort($array, "getSortableValue", [SORT_DESC]);
        $this->stringifyArray($array);
        $this->assertEqualArray(
            $array,
            '{"foo6":"2019-05-01 00:00:01","foo8":"2018-07-02 00:00:01","foo7":"2018-06-01 00:00:01","foo3":"2017-04-01 00:00:01","foo2":"2016-03-01 00:00:01","foo1":"2016-02-01 00:00:02"}'
        );


        $array = [
            "foo1" => DateTime::create("2016-02-01 00:00:02"), // 0
            "foo6" => DateTime::create("2019-05-01 00:00:01"), // 5
            "foo2" => DateTime::create("2016-03-01 00:00:01"), // 1
            "foo7" => DateTime::create("2018-06-01 00:00:01"), // 3
            "foo8" => DateTime::create("2018-07-02 00:00:01"), // 4
            "foo3" => DateTime::create("2017-04-01 00:00:01"), // 2
        ];
        ArrayUtils::sort($array, "getTimestamp", [[SORT_NUMERIC, SORT_ASC]]);
        $this->stringifyArray($array);
        $this->assertEqualArray(
            $array,
            '{"foo1":"2016-02-01 00:00:02","foo2":"2016-03-01 00:00:01","foo3":"2017-04-01 00:00:01","foo7":"2018-06-01 00:00:01","foo8":"2018-07-02 00:00:01","foo6":"2019-05-01 00:00:01"}'
        );

        $array = [
            "foo1" => 1, // 0
            "foo6" => 10.1, // 2
            "foo2" => 100.2, // 4
            "foo7" => 11, // 3
            "foo8" => 2, // 1
            "foo3" => 111, // 5
        ];
        ArrayUtils::sort($array, null, [[SORT_NUMERIC, SORT_ASC]]);
        $this->assertEqualArray(
            $array,
            '{"foo1":1,"foo8":2,"foo6":10.1,"foo7":11,"foo2":100.2,"foo3":111}'
        );

        $this->assertTrue(ArrayUtils::keyExists(['foo' => null], "foo"));
        $this->assertTrue(ArrayUtils::keyExists(['foo' => ['foo2' => ['foo3' => null]]], "foo[foo2][foo3]"));
        $this->assertEqualArray(
            ArrayUtils::getArrayForJavascript($array),
            '{"type":"preparedArray","keys":["foo1","foo8","foo6","foo7","foo2","foo3"],"values":[1,2,10.1,11,100.2,111]}'
        );

        $empty = null;
        ArrayUtils::sort($empty, '', []);
        $this->assertEqualArray($empty, 'null');
        $this->assertEquals("foo[bar][123]", ArrayUtils::joinKeys(['foo', 'bar', '123']));
        $this->assertEquals(["foo"], ArrayUtils::splitKeyString(['foo']));
        $this->assertFalse(ArrayUtils::keyExists(null, "key"));
        $this->assertFalse(ArrayUtils::keyExists([], "key[foo]"));

        $storable = new TestStorable2();
        $storable->name = "foo";
        $this->assertSame($storable->name, ArrayUtils::getValue($storable, "name"));
        $this->assertNull(ArrayUtils::getValue($storable, "nameNotExist"));
    }

    public function testExceptionSortFlag()
    {
        $this->assertExceptionOnCall(function () {
            $array = [
                "foo1" => 1, // 0
                "foo6" => 10.1, // 2
                "foo2" => 100.2, // 4
                "foo7" => 11, // 3
                "foo8" => 2, // 1
                "foo3" => 111, // 5
            ];
            ArrayUtils::sort($array, 'foo1', []);
        });
    }

    /**
     * Assert equal array
     * @param mixed $array
     * @param mixed $expected
     */
    private function assertEqualArray(mixed $array, mixed $expected): void
    {
        $this->assertEquals($expected, json_encode($array));
    }

    /**
     * Stringify array
     * @param mixed $array
     */
    private function stringifyArray(mixed &$array): void
    {
        foreach ($array as $key => $value) {
            $array[$key] = $value instanceof DateTime ? $value->format("Y-m-d H:i:s") : $value;
        }
    }
}
