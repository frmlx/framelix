<?php

namespace Db;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\Sql;

require_once __DIR__ . "/SqlTestBase.php";

final class MysqlTest extends SqlTestBase
{
    public ?int $setupTestDbType = Sql::TYPE_MYSQL;

    protected function createTestTable(): void
    {
        $db = $this->getDb();
        $table = 'condition_tests';
        $db->query("DROP TABLE IF EXISTS $table");
        $db->query("
            CREATE TABLE `$table` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `date_a` DATE NULL,
                `date_b` DATE NULL,
                `text_a` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
                `text_b` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
                `int_a` INTEGER NULL,
                `int_b` INTEGER NULL,
                `jsondata` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB
        ");
    }

    /**
     * @depends testCreate
     */
    public function testExceptionConnectError()
    {
        $this->assertExceptionOnCall(function () {
            $db = Mysql::get('test', false);
            $db->disconnect();
            // connect error simulate with wrong password
            $db->password = "=!'§$%&%&(&/(/&(";
            $db->connect();
        });
    }
}