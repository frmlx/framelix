<?php

namespace Framelix\FramelixTests;

use Framelix\Framelix\Db\Sql;

use function get_class;
use function str_ends_with;

/**
 * A test case specifically designed to run tests again each available db database type that supports
 * all db and storable features of framelix
 */
abstract class TestCaseDbTypes extends TestCase
{
    public ?int $currentDbType = null;

    public function setUp(): void
    {
        parent::setUp();
        $className = get_class($this);
        if (str_ends_with($className, 'MysqlTest')) {
            $this->currentDbType = Sql::TYPE_MYSQL;
        }
        if (str_ends_with($className, 'SqliteTest')) {
            $this->currentDbType = Sql::TYPE_SQLITE;
        }
        switch ($this->currentDbType) {
            case Sql::TYPE_MYSQL:
                \Framelix\Framelix\Config::addMysqlConnection(
                    'test',
                    'unittests',
                    'localhost',
                    'root',
                    'app'
                );
                break;
            case Sql::TYPE_SQLITE:
                $file = FRAMELIX_USERDATA_FOLDER . "/test.db";
                \Framelix\Framelix\Config::addSqliteConnection(
                    'test',
                    $file
                );
                break;
        }
    }
}