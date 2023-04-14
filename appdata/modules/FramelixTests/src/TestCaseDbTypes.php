<?php

namespace Framelix\FramelixTests;

use Framelix\Framelix\Db\Sql;
use mysqli;
use Throwable;

use function get_class;
use function pg_close;
use function pg_query;
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
        if (str_ends_with($className, 'PostgresTest')) {
            $this->currentDbType = Sql::TYPE_POSTGRES;
        }
        switch ($this->currentDbType) {
            case Sql::TYPE_POSTGRES:
                $connection = pg_connect("host=postgres  user=postgres password=app");
                try {
                    pg_query($connection, 'CREATE DATABASE unittests');
                } catch (Throwable $e) {
                }
                pg_close($connection);

                \Framelix\Framelix\Config::addPostgresConnection(
                    'test',
                    'unittests',
                    'postgres',
                    'postgres',
                    'app'
                );
                break;
            case Sql::TYPE_MYSQL:
                $connection = new mysqli('mariadb', 'root', 'app', 'mysql');
                $connection->query('CREATE DATABASE IF NOT EXISTS unittests');
                $connection->close();

                \Framelix\Framelix\Config::addMysqlConnection(
                    'test',
                    'unittests',
                    'mariadb',
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