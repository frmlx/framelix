<?php

namespace Db;

use Framelix\Framelix\Db\Sqlite;

final class BasicTestSqliteTest extends BasicTestBase
{
    protected function createTestTable(): void
    {
        $db = $this->getDb();
        $table = 'condition_tests';
        $db->query("DROP TABLE IF EXISTS $table");
        $db->query(
            "
            CREATE TABLE " . $db->quoteIdentifier($table) . " (
                " . $db->quoteIdentifier("id") . " INTEGER PRIMARY KEY AUTOINCREMENT,
                " . $db->quoteIdentifier("date_a") . " DATE NULL,
                " . $db->quoteIdentifier("date_b") . " DATE NULL,
                " . $db->quoteIdentifier("text_a") . " LONGTEXT NULL,
                " . $db->quoteIdentifier("text_b") . " VARCHAR(100) NULL,
                " . $db->quoteIdentifier("int_a") . " INTEGER NULL,
                " . $db->quoteIdentifier("int_b") . " INTEGER NULL,
               " . $db->quoteIdentifier("jsondata") . " LONGTEXT NULL
            )
        "
        );
    }

    /**
     * @depends testCreate
     */
    public function testExceptionConnectError()
    {
        $this->assertExceptionOnCall(function () {
            $db = Sqlite::get('test', false);
            $db->disconnect();
            // connect error simulate with wrong path
            $db->path = FRAMELIX_USERDATA_FOLDER;
            $db->connect();
        });
    }
}