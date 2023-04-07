<?php

namespace Db;

require_once __DIR__ . "/StorableTestBase.php";

use Framelix\Framelix\Db\Sql;

final class MysqlStorableTest extends StorableTestBase
{
  public ?int $setupTestDbType = Sql::TYPE_MYSQL;
}