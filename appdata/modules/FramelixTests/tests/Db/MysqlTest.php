<?php

namespace Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Mysql;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\TestCase;
use ReflectionClass;

use function fopen;

final class MysqlTest extends TestCase
{

    public function testCreate(): void
    {
        $this->expectNotToPerformAssertions();
        Mysql::get('test')->query("DROP TABLE IF EXISTS `dev`");
    }

    /**
     * @depends testCreate
     */
    public function testConditions()
    {
        $db = Mysql::get('test');
        $table = 'condition_tests';
        $db->query("DROP TABLE IF EXISTS $table");
        $db->query(
            "CREATE TABLE `$table` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `date_a` DATE NULL,
                `date_b` DATE NULL,
                `text_a` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
                `text_b` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
                `int_a` INTEGER NULL,
                `int_b` INTEGER NULL,
                `jsondata` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB"
        );

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
        $this->assertCount(
            1,
            $db->fetchColumn(
                $baseQuery . $db->getConditionTruthyFalsy('date_a', false)
            )
        );
        $this->assertCount(
            4,
            $db->fetchColumn(
                $baseQuery . $db->getConditionTruthyFalsy('date_a', true)
            )
        );


        $db->query("DROP TABLE $table");
    }

    /**
     * @depends testCreate
     */
    public function testExceptionConnectError()
    {
        $this->assertExceptionOnCall(function () {
            // connect error simulate with wrong password
            Config::$dbConnections['test']['passwordOld'] = Config::$dbConnections['test']['password'];
            Config::$dbConnections['test']['password'] = "=!'§$%&%&(&/(/&(";
            Mysql::get('test');
        });
    }

    /**
     * @depends testExceptionConnectError
     */
    public function testQueries(): void
    {
        Mysql::$logExecutedQueries = true;
        $db = Mysql::get('test');

        // connect does nothing when already connected
        $db->connect();

        // make sure there is only one instance of a db connection
        $this->assertSame($db, Mysql::get('test'));

        // create dev table
        $this->assertTrue(
            $db->query(
                "CREATE TABLE `dev` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `text` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB"
            )
        );
        $this->assertCount(1, $db->executedQueries);
        // create storable table
        $this->assertTrue(
            $db->query(
                "CREATE TABLE IF NOT EXISTS `" . TestStorable2::class . "` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `text` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB"
            )
        );

        // insert dev text
        $testText = "foobar\"quote\"";
        $testText2 = "foobar\"quote\"2";
        $this->assertTrue(
            $db->insert("dev", ['text' => $testText])
        );

        // check different select fetch formats
        $this->assertEquals(1, $db->getLastInsertId());
        $this->assertEquals(1, $db->getAffectedRows());
        $this->assertEquals($testText, $db->fetchOne("SELECT text FROM dev"));
        $this->assertEquals([$testText], $db->fetchColumn("SELECT text FROM dev"));
        $this->assertEquals([$testText => $testText], $db->fetchColumn("SELECT text, text FROM dev"));
        $this->assertEquals([1 => ["id" => 1, "text" => $testText]], $db->fetchAssoc("SELECT * FROM dev", null, "id"));
        $this->assertEquals([["text" => $testText]], $db->fetchAssoc("SELECT text FROM dev"));
        $this->assertEquals([[$testText]], $db->fetchArray("SELECT text FROM dev"));

        // update entry and check if it has been updated
        $this->assertTrue(
            $db->update("dev", ['text' => $testText2], "id = {0} || id = {anyparamname}", [1, "anyparamname" => 1])
        );
        $this->assertEquals([[$testText2]], $db->fetchArray("SELECT text FROM dev"));

        // delete the entry and check if it has been deleted
        $this->assertTrue(
            $db->delete("dev", "id = 1")
        );
        $this->assertEquals([], $db->fetchArray("SELECT text FROM dev"));
        $this->assertNull($db->fetchAssocOne("SELECT text FROM dev"));
        $this->assertNull($db->fetchOne("SELECT text FROM dev"));

        // re-insert some entries for later tests
        $db->insert("dev", ['text' => $testText]);
        $db->insert("dev", ['text' => $testText]);
        $db->insert("dev", ['text' => $testText]);
        $db->insert("dev", ['text' => $testText]);
        $db->insert("dev", ['text' => $testText]);
        $db->insert("dev", ['text' => null]);
        $db->insert("dev", ['text' => [$testText]]);
        $db->insert("dev", ['text' => 7.6]);
        $db->insert("dev", ['text' => DateTime::create('now')]);
        $db->insert("dev", ['text' => new ReflectionClass(__CLASS__)]);
        $db->insert(TestStorable2::class, ['id' => 6666], "REPLACE");
        $this->assertCount(2, $db->fetchArray("SELECT text FROM dev", null, 2));
    }

    /**
     * @depends testConditions
     */
    public function testExceptionDbQuery()
    {
        $db = Mysql::get('test');
        Config::$devMode = true;

        $this->assertExceptionOnCall(function () use ($db) {
            $db->queryRaw('foo');
        });

        $this->assertExceptionOnCall(function () use ($db) {
            $db->queryRaw('DESCRIBE 1');
        });
    }

    /**
     * @depends testExceptionDbQuery
     */
    public function testExceptionNotExistingFetchIndex()
    {
        $this->assertExceptionOnCall(function () {
            $db = Mysql::get('test');
            $db->fetchAssoc('SELECT text FROM dev', null, 'foo');
        });
    }

    /**
     * @depends testExceptionNotExistingFetchIndex
     */
    public function testExceptionUnsupportedDbValue()
    {
        $this->assertExceptionOnCall(function () {
            $db = Mysql::get('test');
            // a resource is an unsupported db value
            $db->insert("dev", ['text' => fopen(__FILE__, 'r')]);
        });
    }

    /**
     * Drop dev tables after execution
     * @depends testExceptionUnsupportedDbValue
     */
    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        Mysql::get('test')->query("DROP TABLE IF EXISTS `dev`");
    }
}