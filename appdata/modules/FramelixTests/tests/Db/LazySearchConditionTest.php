<?php

namespace Db;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCase;

final class LazySearchConditionTest extends TestCase
{
    public function tests()
    {
        $db = Mysql::get('test');
        $condition = new LazySearchCondition();
        $condition->addColumn("testBool", "testBool", null, "bool");
        // empty query
        $this->assertSame(
            '0',
            $condition->getPreparedCondition($db, '  ')
        );
        $condition = new LazySearchCondition();
        $condition->prependFixedCondition = "foobar";
        $condition->addColumn("testBool", "testBool", null, "bool");
        // with default condition
        $this->assertSame(
            'foobar && 0',
            $condition->getPreparedCondition($db, '  ')
        );
        $this->assertSame(
            'foobar && (`testBool` = 1)',
            $condition->getPreparedCondition($db, '1')
        );

        $condition = new LazySearchCondition();
        $condition->addColumn("testBool", "testBool", null, "bool");
        $condition->addColumn("testInt", "testInt", null, "int");
        $condition->addColumn("testFloat", "testFloat", null, "float");
        $condition->addColumn("testString", "aCustomPropertyName");
        $condition->addColumn("testDate", "testDate", null, Date::class);
        $condition->addColumn("testDateTime", "testDateTime", null, DateTime::class);
        $this->assertSame('1', $condition->getPreparedCondition($db, "*"));
        $this->assertSame('1', $condition->getPreparedCondition($db, "**"));
        $this->assertSame(
            '(`testString` LIKE "%12.10.2020%" || `testDate` = "2020-10-12" || `testDateTime` = "2020-10-11")',
            $condition->getPreparedCondition($db, "12.10.2020")
        );
        $this->assertSame(
            '(`testInt` > 5 || `testFloat` > 5 || `testString` LIKE "%>5%")',
            $condition->getPreparedCondition($db, ">5")
        );
        $this->assertSame(
            '(`testInt` >= 5 || `testFloat` >= 5 || `testString` LIKE "%>=5%")',
            $condition->getPreparedCondition($db, ">=5")
        );
        $this->assertSame(
            '(`testInt` < 5 || `testFloat` < 5 || `testString` LIKE "%<5%")',
            $condition->getPreparedCondition($db, "<5")
        );
        $this->assertSame(
            '(`testInt` <= 5 || `testFloat` <= 5 || `testString` LIKE "%<=5%")',
            $condition->getPreparedCondition($db, "<=5")
        );
        $this->assertSame(
            '(`testInt` = 5 || `testFloat` = 5 || `testString` = "5")',
            $condition->getPreparedCondition($db, "=5")
        );
        $this->assertSame(
            '(`testInt` != 5 || `testFloat` != 5 || `testString` != "5")',
            $condition->getPreparedCondition($db, "!=5")
        );
        $this->assertSame(
            '(`testBool` = 1 || `testInt` = 1 || `testFloat` = 1 || `testString` LIKE "%1%")',
            $condition->getPreparedCondition($db, "1")
        );
        $this->assertSame(
            '(`testBool` = 1 || `testString` LIKE "%' . Lang::get('__framelix_yes__') . '%")',
            $condition->getPreparedCondition($db, Lang::get('__framelix_yes__'))
        );
        $this->assertSame(
            '(`testBool` = 0 || `testString` LIKE "%' . Lang::get('__framelix_no__') . '%")',
            $condition->getPreparedCondition($db, Lang::get('__framelix_no__'))
        );
        // default search behaviour is AND
        $this->assertSame(
            '(`testString` LIKE "%foo%" && `testString` LIKE "%bar%")',
            $condition->getPreparedCondition($db, 'foo    bar')
        );
        // but can be changed to OR
        $this->assertSame(
            '(`testString` LIKE "%foo%" || `testString` LIKE "%bar%")',
            $condition->getPreparedCondition($db, 'foo | bar')
        );
        // not like
        $this->assertSame(
            '(`testString` NOT LIKE "%~foo%")',
            $condition->getPreparedCondition($db, '!~foo')
        );
        // search with enclodes quotes are full sentence search
        $this->assertSame('(`testString` LIKE "%foo bar%")', $condition->getPreparedCondition($db, '"foo bar"'));
        // named property property
        $this->assertSame('(`testInt` = 1)', $condition->getPreparedCondition($db, 'testInt=1'));
        // specificly named property property
        $this->assertSame('(`testString` = "1")', $condition->getPreparedCondition($db, 'aCustomPropertyName=1'));
        // empty query
        $this->assertSame(
            '0',
            $condition->getPreparedCondition($db, '  ')
        );
    }
}