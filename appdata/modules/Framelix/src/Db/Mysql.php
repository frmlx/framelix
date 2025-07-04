<?php

namespace Framelix\Framelix\Db;

use Cstp\Ouced\Db;
use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;

use function mb_strtolower;
use function mysqli_insert_id;
use function mysqli_query;
use function mysqli_real_escape_string;
use function mysqli_report;

use const MYSQLI_REPORT_ALL;

class Mysql extends Sql implements SchemeBuilderRequirementsInterface
{

    public ?mysqli $connection = null;
    public bool|mysqli_result $lastResult = false;

    /**
     * The hostname for the connection
     * @var string
     */
    public string $host;

    /**
     * The username for the connection
     * @var string
     */
    public string $username;

    /**
     * The password for the connection
     * @var string|null
     */
    public string|null $password;

    /**
     * The database for the connection
     * @var string
     */
    public string $database;

    /**
     * The port for the connection
     * @var int|null
     */
    public int|null $port;

    /**
     * The socket path for the connection
     * @var string|null
     */
    public string|null $socket;

    /**
     * @inheritDoc
     */
    public function setConfig(array $configValues): void
    {
        $this->host = $configValues['host'];
        $this->username = $configValues['username'];
        $this->password = $configValues['password'] ?? null;
        $this->port = $configValues['port'] ?? null;
        $this->database = $configValues['database'];
        $this->socket = $configValues['socket'] ?? null;
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
            mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port ?: 3306,
                $this->socket
            );
            $this->connected = true;
        } catch (mysqli_sql_exception $e) {
            throw new FatalError($e->getMessage());
        }
        $this->connection->set_charset('utf8mb4');
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
        $this->connection = null;
    }

    /**
     * @inheritDoc
     */
    public function queryRaw(string $query): bool|mysqli_result
    {
        try {
            $this->lastResult = mysqli_query($this->connection, $query);
            // this code was unable to reproduce in unit tests
            // every mysql error should throw an exception
            // if it does not but result is still false, we throw by hand
            // maybe it's a legacy behaviour of some mysql drivers
            // @codeCoverageIgnoreStart
            if (!$this->lastResult) {
                throw new FatalError("No Mysql Result: " . $this->connection->error);
            }
            // @codeCoverageIgnoreEnd
        } catch (Throwable $e) {
            $this->transactionErrorHandler();
            $errorMessage = "Mysql Error: " . $e->getMessage();
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
     * Get last insert id from last insert query
     * @return int
     */
    public function getLastInsertId(): int
    {
        return (int)mysqli_insert_id($this->connection);
    }

    public function escapeString(string $value): string
    {
        return '"' . mysqli_real_escape_string($this->connection, $value) . '"';
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
        while ($row = $this->lastResult->fetch_array(MYSQLI_NUM)) {
            $fetch[] = $row;
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
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
        while ($row = $this->lastResult->fetch_assoc()) {
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
        $fetch = $this->fetchAssoc(
            "SHOW TABLE STATUS FROM " . $this->quoteIdentifier($this->database)
        );
        foreach ($fetch as $row) {
            $tableName = mb_strtolower($row['Name']);
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
        if ($flushCache) {
            unset($this->cache[$cacheKey]);
        }
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $this->cache[$cacheKey] = $this->fetchAssoc(
            "SHOW FULL FIELDS FROM " . $this->quoteIdentifier($table),
            null,
            'Field'
        );
        return $this->cache[$cacheKey];
    }

    /**
     * @inheritDoc
     */
    public function getTableIndexes(string $table, bool $flushCache = false): array
    {
        $cacheKey = __METHOD__ . "_" . $table;
        if ($flushCache) {
            unset($this->cache[$cacheKey]);
        }
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $rows = $this->fetchAssoc("SHOW INDEXES FROM " . $this->quoteIdentifier($table));
        foreach ($rows as $row) {
            $this->cache[$cacheKey][$row['Key_name']][$row['Seq_in_index']] = $row;
        }
        return $this->cache[$cacheKey];
    }

}