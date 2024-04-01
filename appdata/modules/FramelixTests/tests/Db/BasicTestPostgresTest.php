<?php

namespace Db;

use Framelix\Framelix\Db\Postgres;
use PHPUnit\Framework\Attributes\Depends;

final class BasicTestPostgresTest extends BasicTestBase
{
    protected function createTestTable(): void
    {
        $db = $this->getDb();
        $table = 'condition_tests';
        $db->query("DROP TABLE IF EXISTS $table");
        $db->query(
            "
            CREATE TABLE `$table` (
                `id` SERIAL PRIMARY KEY,
                `date_a` DATE NULL,
                `date_b` DATE NULL,
                `text_a` TEXT NULL,
                `text_b` VARCHAR(100) NULL,
                `int_a` INTEGER NULL,
                `int_b` INTEGER NULL,
                `jsondata` TEXT NULL
            )
        "
        );
    }

    #[Depends("testCleanup")]
    public function testExceptionConnectError()
    {
        $this->assertExceptionOnCall(function () {
            $db = Postgres::get('test', false);
            $db->disconnect();
            // connect error simulate with wrong password
            $db->password = "=!'§$%&%&(&/(/&(";
            $db->connect();
        });
    }
}