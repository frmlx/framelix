<?php

namespace Db;

require_once __DIR__ . "/StorableTestBase.php";

use Framelix\Framelix\Db\Sql;

final class SqliteStorableTest extends StorableTestBase
{
  public ?int $setupTestDbType = Sql::TYPE_SQLITE;
}