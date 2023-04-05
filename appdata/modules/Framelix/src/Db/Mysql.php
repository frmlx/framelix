<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;

use function mysqli_insert_id;
use function mysqli_query;
use function mysqli_real_escape_string;
use function mysqli_report;

use const MYSQLI_REPORT_ALL;

class Mysql extends Sql
{
    /**
     * MySQLi connection resource
     * @var mysqli|null
     */
    public ?mysqli $mysqli = null;

    /**
     * Last query result
     * @var bool|mysqli_result
     */
    public bool|mysqli_result $lastResult = false;

    public string $host;
    public string $username;
    public string|null $password;
    public int|null $port;
    public string $database;
    public string|null $socket;

    public function setConfig(array $configValues): void
    {
        $this->host = $configValues['host'];
        $this->username = $configValues['username'];
        $this->password = $configValues['password'] ?? null;
        $this->port = $configValues['port'] ?? null;
        $this->database = $configValues['database'];
        $this->socket = $configValues['socket'] ?? null;
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }
        try {
            mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);
            $this->mysqli = new mysqli(
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
        $this->mysqli->set_charset('utf8mb4');
    }

    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }
        $this->connected = false;
        $this->mysqli?->close();
        $this->mysqli = null;
    }

    /**
     * Execute the raw query without any framework modification
     * @param string $query
     * @return bool|mysqli_result
     */
    public function queryRaw(string $query): bool|mysqli_result
    {
        try {
            $this->lastResult = mysqli_query($this->mysqli, $query);
            // this code was unable to reproduce in unit tests
            // every mysql error should throw an exception
            // if it does not but result is still false, we throw by hand
            // maybe it's a legacy behaviour of some mysql drivers
            // @codeCoverageIgnoreStart
            if (!$this->lastResult) {
                throw new FatalError("No Mysql Result");
            }
            // @codeCoverageIgnoreEnd
        } catch (Throwable $e) {
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
        return (int)mysqli_insert_id($this->mysqli);
    }

    public function escapeString(string $value): string
    {
        return '"' . mysqli_real_escape_string($this->mysqli, $value) . '"';
    }
}