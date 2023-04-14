<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use PgSql\Connection;
use PgSql\Result;
use Throwable;

use function pg_close;
use function pg_escape_string;
use function pg_fetch_assoc;
use function pg_fetch_row;
use function pg_query;

class Postgres extends Sql
{
    public ?Connection $connection = null;
    public bool|Result $lastResult = false;

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

    public string $quoteChars = '""';

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
            $this->connection = pg_connect("host={$this->host} " . ($this->port ? 'port=' . (int)$this->port : '') . " dbname={$this->database} user={$this->username} password='{$this->password}' options='--client_encoding=UTF8'");
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
        if ($this->connection) {
            pg_close($this->connection);
        }
        $this->connection = null;
    }

    /**
     * @inheritDoc
     */
    public function queryRaw(string $query): bool|Result
    {
        try {
            $this->lastResult = pg_query($this->connection, $query);
        } catch (Throwable $e) {
            $errorMessage = "Postgres Error: " . $e->getMessage();
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
        return (int)$this->fetchOne("SELECT LASTVAL()");
    }

    public function escapeString(string $value): string
    {
        return '\'' . pg_escape_string($this->connection, $value) . '\'';
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
        while ($row = pg_fetch_row($this->lastResult)) {
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
        while ($row = pg_fetch_assoc($this->lastResult)) {
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
}