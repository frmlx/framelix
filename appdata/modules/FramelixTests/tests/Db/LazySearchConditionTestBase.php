<?php

namespace Db;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCaseDbTypes;

abstract class LazySearchConditionTestBase extends TestCaseDbTypes
{
    public function tests()
    {
        $db = Sql::get('test');
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
            'foobar AND 0',
            $condition->getPreparedCondition($db, '  ')
        );
        $this->assertSame(
            'foobar AND (`testBool` = 1)',
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
        $expectedString = $db->prepareQuery(
            "(`testString` LIKE " . $db->escapeValue("%12.10.2020%") . " OR `testDate` = " . $db->escapeValue(
                "2020-10-12"
            ) . " OR `testDateTime` = " . $db->escapeValue("2020-10-11") . ")"
        );
        $this->assertSame(
            $expectedString,
            $condition->getPreparedCondition($db, "12.10.2020")
        );
        $this->assertSame(
            '(`testInt` > 5 OR `testFloat` > 5 OR `testString` LIKE ' . $db->escapeValue('%>5%') . ')',
            $condition->getPreparedCondition($db, ">5")
        );
        $this->assertSame(
            '(`testInt` >= 5 OR `testFloat` >= 5 OR `testString` LIKE ' . $db->escapeValue('%>=5%') . ')',
            $condition->getPreparedCondition($db, ">=5")
        );
        $this->assertSame(
            '(`testInt` < 5 OR `testFloat` < 5 OR `testString` LIKE ' . $db->escapeValue('%<5%') . ')',
            $condition->getPreparedCondition($db, "<5")
        );
        $this->assertSame(
            '(`testInt` <= 5 OR `testFloat` <= 5 OR `testString` LIKE ' . $db->escapeValue('%<=5%') . ')',
            $condition->getPreparedCondition($db, "<=5")
        );
        $this->assertSame(
            '(`testInt` = 5 OR `testFloat` = 5 OR `testString` = ' . $db->escapeValue('5') . ')',
            $condition->getPreparedCondition($db, "=5")
        );
        $this->assertSame(
            '(`testInt` != 5 OR `testFloat` != 5 OR `testString` != ' . $db->escapeValue('5') . ')',
            $condition->getPreparedCondition($db, "!=5")
        );
        $this->assertSame(
            '(`testBool` = 1 OR `testInt` = 1 OR `testFloat` = 1 OR `testString` LIKE ' . $db->escapeValue('%1%') . ')',
            $condition->getPreparedCondition($db, "1")
        );
        $this->assertSame(
            '(`testBool` = 1 OR `testString` LIKE ' . $db->escapeValue('%' . Lang::get('__framelix_yes__') . '%') . ')',
            $condition->getPreparedCondition($db, Lang::get('__framelix_yes__'))
        );
        $this->assertSame(
            '(`testBool` = 0 OR `testString` LIKE ' . $db->escapeValue('%' . Lang::get('__framelix_no__') . '%') . ')',
            $condition->getPreparedCondition($db, Lang::get('__framelix_no__'))
        );
        // default search behaviour is AND
        $this->assertSame(
            '(`testString` LIKE ' . $db->escapeValue('%foo%') . ' AND `testString` LIKE ' . $db->escapeValue(
                '%bar%'
            ) . ')',
            $condition->getPreparedCondition($db, 'foo    bar')
        );
        // but can be changed to OR
        $this->assertSame(
            '(`testString` LIKE ' . $db->escapeValue('%foo%') . ' OR `testString` LIKE ' . $db->escapeValue(
                '%bar%'
            ) . ')',
            $condition->getPreparedCondition($db, 'foo | bar')
        );
        // not like
        $this->assertSame(
            '(`testString` NOT LIKE ' . $db->escapeValue('%~foo%') . ')',
            $condition->getPreparedCondition($db, '!~foo')
        );
        // search with enclodes quotes are full sentence search
        $this->assertSame(
            '(`testString` LIKE ' . $db->escapeValue('%foo bar%') . ')',
            $condition->getPreparedCondition($db, '"foo bar"')
        );
        // named property
        $this->assertSame('(`testInt` = 1)', $condition->getPreparedCondition($db, 'testInt=1'));
        // specificly named property
        $this->assertSame(
            '(`testString` = ' . $db->escapeValue('1') . ')',
            $condition->getPreparedCondition($db, 'aCustomPropertyName=1')
        );
        // empty query
        $this->assertSame(
            '0',
            $condition->getPreparedCondition($db, '  ')
        );
    }
}