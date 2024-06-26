<?php

namespace Db;

use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Sql;
use Framelix\FramelixTests\TestCaseDbTypes;
use PHPUnit\Framework\Attributes\Depends;
use ReflectionClass;

use function fopen;

abstract class BasicTestBase extends TestCaseDbTypes
{
    abstract protected function createTestTable(): void;

    public function getDb(): Sql
    {
        return Sql::get('test');
    }

    public function testCreate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->createTestTable();
    }

    #[Depends("testCreate")]
    public function testConditions()
    {
        $db = $this->getDb();
        $table = 'condition_tests';
        $this->createTestTable();

        $dateBase = Date::create("2022-03-15");

        $db->insert($table, ['date_a' => null, 'date_b' => $dateBase->clone()->dateTime->modify("- 10 days")]);
        $db->insert(
            $table,
            [
                'date_a' => $dateBase->clone()->dateTime->modify("- 10 days"),
                'date_b' => $dateBase->clone()->dateTime->modify("- 5 days")
            ]
        );
        $db->insert(
            $table,
            ['date_a' => $dateBase->clone()->dateTime->modify("- 5 days"), 'date_b' => $dateBase->clone()->dateTime]
        );
        $db->insert(
            $table,
            ['date_a' => $dateBase->clone(), 'date_b' => $dateBase->clone()->dateTime->modify("+ 5 days")]
        );
        $db->insert($table, ['date_a' => $dateBase->clone()->dateTime->modify("+ 5 days"), 'date_b' => null]);

        $baseQuery = "SELECT id FROM $table WHERE ";
        $this->assertCount(
            2,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange(
                    null,
                    $dateBase->clone()->dateTime->modify("- 2 days"),
                    'date_b'
                )
            )
        );
        $this->assertCount(
            1,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange(
                    $dateBase->clone()->dateTime->modify("- 2 days"),
                    $dateBase->clone()->dateTime->modify("+ 2 days"),
                    'date_b'
                )
            )
        );
        $this->assertCount(
            4,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange(
                    $dateBase->clone()->dateTime->modify("- 1 days"),
                    $dateBase,
                    'date_b',
                    'month'
                )
            )
        );
        $this->assertCount(
            4,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange($dateBase, $dateBase, 'date_b', 'year')
            )
        );
        $this->assertCount(
            2,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange(
                    $dateBase->clone()->dateTime->modify("- 2 days"),
                    null,
                    'date_b'
                )
            )
        );
        $this->assertCount(
            4,
            $db->fetchColumn($baseQuery . $db->getConditionDbDateInPhpRange($dateBase, null, 'date_b', 'month'))
        );
        $this->assertCount(
            3,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDbDateInPhpRange(
                    $dateBase->clone()->dateTime->modify("- 12 days"),
                    $dateBase->clone()->dateTime->modify("+ 2 days"),
                    'date_b'
                )
            )
        );
        $this->assertCount(
            2,
            $db->fetchColumn(
                $baseQuery . $db->getConditionDateRangeOverlaps(
                    $dateBase->clone()->dateTime->modify("- 2 days"),
                    $dateBase->clone()->dateTime->modify("+ 2 days"),
                    'date_a',
                    'date_b'
                )
            )
        );
        $this->assertCount(
            1,
            $db->fetchColumn(
                $baseQuery . $db->getConditionPhpDateInDbRange(
                    $dateBase->clone()->dateTime->modify("- 2 days"),
                    'date_a',
                    'date_b'
                )
            )
        );
        $this->assertCount(
            5,
            $db->fetchColumn(
                $baseQuery . $db->getConditionPhpDateInDbRange($dateBase, 'date_a', 'date_b', 'month')
            )
        );
        $this->assertCount(
            5,
            $db->fetchColumn(
                $baseQuery . $db->getConditionPhpDateInDbRange($dateBase, 'date_a', 'date_b', 'year')
            )
        );
        $db->query("DROP TABLE $table");
    }

    #[Depends("testConditions")]
    public function testQueries(): void
    {
        Sql::$logExecutedQueries = true;
        $db = $this->getDb();

        // connect does nothing when already connected
        $db->connect();

        // make sure there is only one instance of a db connection
        $this->assertSame($db, $this->getDb());

        $table = "condition_tests";
        $this->createTestTable();
        $this->assertCount(2, $db->executedQueries);

        // insert test text
        $testText = "foobar\"quote\" '😋' '😋'\t\r";
        $testText2 = "foobar\"quote\"2 '😋'";
        $this->assertNotNull(
            $db->insert($table, ['text_a' => $testText])
        );

        // check different select fetch formats
        $this->assertEquals(1, $db->getLastInsertId());
        $this->assertEquals($testText, $db->fetchOne("SELECT text_a FROM $table"));
        $this->assertEquals([$testText], $db->fetchColumn("SELECT text_a FROM $table"));
        $this->assertEquals([$testText => $testText], $db->fetchColumn("SELECT text_a, text_a FROM $table"));
        $this->assertEquals([
            1 => [
                'id' => '1',
                'text_a' => $testText,
                'date_a' => null,
                'date_b' => null,
                'text_b' => null,
                'int_a' => null,
                'int_b' => null,
                'jsondata' => null
            ]
        ], $db->fetchAssoc("SELECT * FROM $table", null, "id"));
        $this->assertEquals([["text_a" => $testText]], $db->fetchAssoc("SELECT text_a FROM $table"));
        $this->assertEquals([[$testText]], $db->fetchArray("SELECT text_a FROM $table"));

        // update entry and check if it has been updated
        $this->assertNotNull(
            $db->update($table, ['text_a' => $testText2], "id = {0} OR id = {anyparamname}", [1, "anyparamname" => 1])
        );
        $this->assertEquals([[$testText2]], $db->fetchArray("SELECT text_a FROM $table"));

        // delete the entry and check if it has been deleted
        $this->assertNotNull(
            $db->delete($table, "id = 1")
        );
        $this->assertEquals([], $db->fetchArray("SELECT text_a FROM $table"));
        $this->assertNull($db->fetchAssocOne("SELECT text_a FROM $table"));
        $this->assertNull($db->fetchOne("SELECT text_a FROM $table"));

        // re-insert some entries for later tests
        $db->insert($table, ['text_a' => $testText]);
        $db->insert($table, ['text_a' => $testText]);
        $db->insert($table, ['text_a' => $testText]);
        $db->insert($table, ['text_a' => $testText]);
        $db->insert($table, ['text_a' => $testText]);
        $db->insert($table, ['text_a' => null]);
        $db->insert($table, ['text_a' => [$testText]]);
        $db->insert($table, ['text_a' => 7.6]);
        $db->insert($table, ['text_a' => DateTime::create('now')]);
        $prettyLongText = (string)new ReflectionClass(__CLASS__);
        $prettyLongText .= "with emojiiiiis 😋😋😋";
        $db->insert($table, ['text_a' => $prettyLongText]);
        $this->assertSame($prettyLongText, $db->fetchOne("SELECT text_a FROM $table ORDER BY id DESC LIMIT 1"));
        $this->assertCount(2, $db->fetchArray("SELECT text_a FROM $table", null, 2));
    }

    #[Depends("testQueries")]
    public function testExceptionDbQuery()
    {
        $db = $this->getDb();

        $this->assertExceptionOnCall(function () use ($db) {
            $db->queryRaw('SELECT QUACK FROM DUCK');
        });

        $this->assertExceptionOnCall(function () use ($db) {
            $db->queryRaw('DESCRIBE 1');
        });
    }

    #[Depends("testExceptionDbQuery")]
    public function testExceptionNotExistingFetchIndex()
    {
        $this->assertExceptionOnCall(function () {
            $db = $this->getDb();
            $db->fetchAssoc('SELECT text_a FROM condition_tests', null, 'foo');
        });
    }

    #[Depends("testExceptionNotExistingFetchIndex")]
    public function testExceptionUnsupportedDbValue()
    {
        $this->assertExceptionOnCall(function () {
            $db = $this->getDb();
            // a resource is an unsupported db value
            $db->insert("condition_tests", ['text_a' => fopen(__FILE__, 'r')]);
        });
    }

    #[Depends("testExceptionUnsupportedDbValue")]
    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        $this->getDb()->query("DROP TABLE IF EXISTS `condition_tests`");
        $this->getDb()->disconnect();
    }
}