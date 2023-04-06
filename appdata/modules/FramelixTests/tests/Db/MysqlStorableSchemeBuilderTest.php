<?php


namespace Db;

use Framelix\Framelix\Db\Sql;

require_once __DIR__ . "/StorableSchemeBuilderTestBase.php";

final class MysqlStorableSchemeBuilderTest extends StorableSchemeBuilderTestBase
{
    public ?int $setupTestDbType = Sql::TYPE_MYSQL;
}