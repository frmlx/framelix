<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use SQLite3;
use SQLite3Result;
use Throwable;

use function explode;
use function implode;
use function str_replace;
use function strpos;
use function substr;

class Sqlite extends Sql implements SchemeBuilderRequirementsInterface
{
    public SQLite3|null $connection;
    public SQLite3Result|bool|null $lastResult = null;

    /**
     * The path to the database file
     * @var string
     */
    public string $path;

    /**
     * @inheritDoc
     */
    public function setConfig(array $configValues): void
    {
        $this->path = $configValues['path'];
    }

    /**
     * @inheritDoc
     */
    public function connect(): void
    {
        if ($this->connected) {
            return;
        }
        try {
            $this->connection = new SQLite3($this->path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->connection->enableExceptions(true);
            // 5 minutes timeout
            $this->connection->busyTimeout(60000 * 5);
            if ($this->connection->lastErrorCode()) {
                throw new FatalError($this->connection->lastErrorMsg());
            }
            $this->connected = true;
        } catch (Throwable $e) {
            throw new FatalError($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }
        parent::disconnect();
        $this->connection?->close();
    }

    /**
     * Exce raw query without expecting any result
     * This is a sqlite special to execute multiple queries in one command
     * @param string $query
     * @return bool
     */
    public function execRaw(string $query): bool
    {
        try {
            $this->lastResult = $this->connection->exec($query);
            if (!$this->lastResult) {
                throw new FatalError($this->connection->lastErrorMsg());
            }
        } catch (Throwable $e) {
            $errorMessage = "Sqlite Error (" . $this->connection->lastErrorCode() . "): " . $e->getMessage();
            if (Config::$devMode) {
                $errorMessage .= " in query: " . $query;
            }
            throw new FatalError($errorMessage);
        }
        $this->executedQueriesCount++;
        if (self::$logExecutedQueries) {
            $this->executedQueries[] = $query;
        }
        return $this->lastResult;
    }

    /**
     * @inheritDoc
     */
    public function queryRaw(string $query): bool|SQLite3Result
    {
        try {
            $this->lastResult = $this->connection->query($query);
            if (!$this->lastResult) {
                throw new FatalError("No Sqlite Result: " . $this->connection->lastErrorMsg());
            }
        } catch (Throwable $e) {
            $errorMessage = "Sqlite Error (" . $this->connection->lastErrorCode() . "): " . $e->getMessage();
            if (Config::$devMode) {
                $errorMessage .= " in query: " . $query;
            }
            throw new FatalError($errorMessage);
        }
        $this->executedQueriesCount++;
        if (self::$logExecutedQueries) {
            $this->executedQueries[] = $query;
        }
        return $this->lastResult;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId(): int
    {
        return $this->connection->lastInsertRowID();
    }

    public function escapeString(string $value): string
    {
        return '\'' . $this->connection->escapeString($value) . '\'';
    }

    /**
     * @inheritDoc
     */
    public function fetchArray(
        string $query,
        ?array $parameters = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $this->query($query, $parameters);
        while ($row = $this->lastResult->fetchArray(SQLITE3_NUM)) {
            $fetch[] = $row;
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
        $this->lastResult->finalize();
        return $fetch;
    }

    /**
     * @inheritDoc
     */
    public function fetchAssoc(
        string $query,
        ?array $parameters = null,
        ?string $valueAsArrayIndex = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $this->query($query, $parameters);
        while ($row = $this->lastResult->fetchArray(SQLITE3_ASSOC)) {
            if (is_string($valueAsArrayIndex)) {
                if (!isset($row[$valueAsArrayIndex])) {
                    throw new FatalError(
                        "Field '$valueAsArrayIndex' does not exist in SQL Result or is null"
                    );
                }
                $fetch[$row[$valueAsArrayIndex]] = $row;
            } else {
                $fetch[] = $row;
            }
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
        $this->lastResult->finalize();
        return $fetch;
    }

    /**
     * @inheritDoc
     */
    public function getTables(bool $flushCache = false): array
    {
        $cacheKey = __METHOD__;
        if (!$flushCache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $existingTables = [];
        $fetch = $this->fetchAssoc("SELECT name FROM sqlite_schema WHERE type ='table' AND name NOT LIKE 'sqlite_%'");
        foreach ($fetch as $row) {
            $tableName = mb_strtolower($row['name']);
            $existingTables[$tableName] = $tableName;
        }
        $this->cache[$cacheKey] = $existingTables;
        return $existingTables;
    }

    /**
     * @inheritDoc
     */
    public function getTableColumns(string $table, bool $flushCache = false): array
    {
        $cacheKey = __METHOD__ . "_" . $table;
        if (!$flushCache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $result = $this->fetchOne(
            "            
            SELECT sql 
            FROM sqlite_master 
            WHERE tbl_name = " . $this->escapeValue($table) . "  AND type = 'table'
        "
        );
        $arr = [];
        if ($result) {
            $result = substr($result, strpos($result, "(") + 1, -1);
            $columns = explode(", `", str_replace("\"", "`", $result));
            foreach ($columns as $column) {
                $parts = explode("`", trim($column, " `"));
                $columnName = $parts[0];
                unset($parts[0]);
                $arr[$columnName] = trim(implode("`", $parts));
            }
        }
        $this->cache[$cacheKey] = $arr;
        return $arr;
    }

    /**
     * @inheritDoc
     */
    public function getTableIndexes(string $table, bool $flushCache = false): array
    {
        $cacheKey = __METHOD__ . "_" . $table;
        if (!$flushCache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $results = $this->fetchAssoc(
            "            
            SELECT name, sql 
            FROM sqlite_master 
            WHERE tbl_name = " . $this->escapeValue($table) . "  AND type = 'index'
        ",
            null,
            "name"
        );
        $arr = [];
        foreach ($results as $result) {
            $arr[$result['name']] = $result['sql'];
        }
        $this->cache[$cacheKey] = $arr;
        return $this->cache[$cacheKey];
    }
}