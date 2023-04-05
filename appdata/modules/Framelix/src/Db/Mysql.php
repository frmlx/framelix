<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\QuickCast;
use JetBrains\PhpStorm\ExpectedValues;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;

use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function mb_substr;
use function mysqli_affected_rows;
use function mysqli_insert_id;
use function mysqli_query;
use function mysqli_real_escape_string;
use function mysqli_report;
use function preg_match_all;
use function reset;
use function str_replace;
use function str_starts_with;

use const MYSQLI_NUM;
use const MYSQLI_REPORT_ALL;

/**
 * Mysql database handling
 * This is the framelix default and is used internally everywhere
 */
class Mysql
{
    /**
     * Instances
     * @var self[]
     */
    public static array $instances = [];

    /**
     * For debugging you log executed queries into $executedQueries
     * @var bool
     */
    public static bool $logExecutedQueries = false;

    /**
     * The connection config values
     * @var array
     */
    public array $connectionConfig = [];

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

    /**
     * The id for this instance
     * @var string
     */
    public string $id;

    /**
     * Executed queries
     * @var array
     */
    public array $executedQueries = [];

    /**
     * Executed queries count
     * @var int
     */
    public int $executedQueriesCount = 0;

    /**
     * Get mysql instance for given id
     * @param string $id
     * @param bool $connect If true, then connect instantly
     * @return Mysql
     */
    public static function get(string $id = FRAMELIX_MODULE, bool $connect = true): Mysql
    {
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }
        $instance = new self();
        $instance->id = $id;
        if ($connect) {
            $instance->connect();
        }
        self::$instances[$id] = $instance;
        return $instance;
    }


    /**
     * Get a condition that checks if a given value is in given stored array
     * @param string $propertyName
     * @param string $keyPath Same rules as self::getConditionJsonGetValue()
     * @param mixed $value The value must match the value in the given path
     *     Also make sure they are the same type as "20192" is NOT equal 20192 and will return false
     * @param bool $compareEach If true and $value is an array, compare each individual array value separately and return true if any of them match
     * @param string|null $fixedCast Cast value to this value type to make sure comparison works
     * @return string
     */
    public function getConditionJsonContainsArrayValue(
        string $propertyName,
        string $keyPath,
        mixed $value,
        bool $compareEach = false,
        #[ExpectedValues(values: ['int', 'string'])] ?string $fixedCast = null
    ): string {
        if (is_array($value) && $compareEach) {
            // empty array, is false anyway
            if (!$value) {
                return '0';
            }
            $condition = [];
            foreach ($value as $v) {
                $condition[] = self::getConditionJsonContainsArrayValue($propertyName, $keyPath, $v, false, $fixedCast);
            }
            return "(" . implode(" || ", $condition) . ")";
        }
        return 'JSON_CONTAINS(' . self::getConditionJsonGetValue(
                $propertyName,
                $keyPath
            ) . ', ' . $this->escapeValue(
                $value,
                $fixedCast
            ) . ') = 1';
    }

    /**
     * Get a condition that fetches json fields array/object value
     *
     * Data for the examples: {"id" => 1, "users" => [100, 200]}
     * $.id fetches the id of the object => 1
     * $.users[0] fetches the first value of the "users" property, which is an array => 100
     *
     * Data for the examples: [100, 200, 300]
     * $[0] fetches the key 0 from array => 100
     * $[2] fetches the key 2 from array => 300
     *
     * @param string $propertyName
     * @param string $keyPath
     * @return string
     */
    public function getConditionJsonGetValue(
        string $propertyName,
        string $keyPath
    ): string {
        if (!str_starts_with($keyPath, "\$")) {
            throw new FatalError('keyPath must start with $');
        }
        return 'JSON_EXTRACT(' . $propertyName . ', \'' . $keyPath . '\')';
    }

    /**
     * Get a condition that check for field to be truthy/false
     * @param string $dbField
     * @param bool $truthyFalsy
     * @return string
     */
    public function getConditionTruthyFalsy(
        string $dbField,
        bool $truthyFalsy
    ): string {
        $condition = "($dbField IS NOT NULL && TRIM($dbField) != '' && $dbField != '0')";
        if (!$truthyFalsy) {
            $condition = "!" . $condition;
        }
        return $condition;
    }

    /**
     * Get a condition for given range overlaps a range in the database
     * If a start time is empty/null - It will converted to 0000-01-01
     * If a end time is empty/null - It will be converted to 9999-12-31
     * @param mixed $dateStart
     * @param mixed $dateEnd
     * @param string $dbFieldStart
     * @param string $dbFieldEnd
     * @return string
     */
    public function getConditionDateRangeOverlaps(
        mixed $dateStart,
        mixed $dateEnd,
        string $dbFieldStart,
        string $dbFieldEnd
    ): string {
        $dateStart = !($dateStart instanceof DateTime) ? DateTime::create($dateStart) : $dateStart;
        $dateEnd = !($dateEnd instanceof DateTime) ? DateTime::create($dateEnd) : $dateEnd;
        $sqlDateStart = $dateStart ? $dateStart->format('Y-m-d') : '0000-01-01';
        $sqlDateEnd = $dateEnd ? $dateEnd->format('Y-m-d') : '9999-12-31';
        return "('$sqlDateStart' <= DATE(IFNULL($dbFieldEnd, '9999-12-31')) && DATE(IFNULL($dbFieldStart, '0000-01-01')) <= '$sqlDateEnd')";
    }

    /**
     * Get a condition that check if date in database is inside given php date range
     * @param mixed $rangeStart If not set or invalid it will not be checked
     * @param mixed $rangeEnd If not set or invalid it will not be checked
     * @param string $dbField The date field in the database
     * @param string $compareMethod date, month, year
     * @param string $conditionOnEmptyDates If both $rangeStart and $rangeEnd is invalid, then this condition will be returned instead
     * @return string
     */
    public function getConditionDbDateInPhpRange(
        mixed $rangeStart,
        mixed $rangeEnd,
        string $dbField,
        #[ExpectedValues(values: ["date", "month", "year"])]
        string $compareMethod = "date",
        string $conditionOnEmptyDates = "1"
    ): string {
        $dbField = "DATE($dbField)";
        if ($compareMethod === "month") {
            $dbField = "CAST(DATE_FORMAT($dbField, '%Y%m') as UNSIGNED INTEGER)";
        } elseif ($compareMethod === "year") {
            $dbField = "YEAR($dbField)";
        }
        $condition = [];
        if ($rangeStart) {
            $rangeStart = DateTime::create($rangeStart);
            if ($rangeStart) {
                if ($compareMethod === "month") {
                    $compareValue = (int)$rangeStart->format("Ym");
                } elseif ($compareMethod === "year") {
                    $compareValue = (int)$rangeStart->getYear();
                } else {
                    $compareValue = $rangeStart->format('Y-m-d');
                }
                $condition[] = "$dbField >= " . $this->escapeValue($compareValue);
            }
        }
        if ($rangeEnd) {
            $rangeEnd = DateTime::create($rangeEnd);
            if ($rangeEnd) {
                if ($compareMethod === "month") {
                    $compareValue = (int)$rangeEnd->format("Ym");
                } elseif ($compareMethod === "year") {
                    $compareValue = $rangeEnd->getYear();
                } else {
                    $compareValue = $rangeEnd->format('Y-m-d');
                }
                $condition[] = "$dbField <= " . $this->escapeValue($compareValue);
            }
        }
        if ($condition) {
            return "(" . implode(" && ", $condition) . ")";
        } else {
            return "($conditionOnEmptyDates)";
        }
    }

    /**
     * Get a condition that check if date in php is inside given date range database
     * @param mixed $date
     * @param string $dbFieldStart If null in DB it will converted to 0000-01-01
     * @param string $dbFieldEnd If null in DB it will converted to 9999-12-31
     * @param string $compareMethod date, month, year
     * @return string
     */
    public function getConditionPhpDateInDbRange(
        mixed $date,
        string $dbFieldStart,
        string $dbFieldEnd,
        #[ExpectedValues(values: ["date", "month", "year"])]
        string $compareMethod = "date"
    ): string {
        $dbFieldStart = "DATE(IFNULL($dbFieldStart, '0000-01-01'))";
        $dbFieldEnd = "DATE(IFNULL($dbFieldEnd, '9999-12-31'))";
        $compareValue = DateTime::create($date);
        switch ($compareMethod) {
            case 'month':
                $dbFieldStart = "CAST(DATE_FORMAT($dbFieldStart, '%Y%m') as UNSIGNED INTEGER)";
                $dbFieldEnd = "CAST(DATE_FORMAT($dbFieldEnd, '%Y%m') as UNSIGNED INTEGER)";
                $compareValue = (int)$compareValue->format("Ym");
                break;
            case 'year':
                $dbFieldStart = "YEAR($dbFieldStart)";
                $dbFieldEnd = "YEAR($dbFieldEnd)";
                $compareValue = (int)$compareValue->format("Y");
                break;
            default:
                $compareValue = $compareValue->format('Y-m-d');
        }
        return "('$compareValue' BETWEEN $dbFieldStart AND $dbFieldEnd)";
    }

    /**
     * Escape any value for database usage
     * @param mixed $value *
     * @param string|null $fixedCast Force a cast to string|int|bool when escaping to fix issues when different type to db will result in loss performance
     *  Attention: NULL will stay as is, array values (each value separately) will be casted as well
     * @return string|int|float
     */
    public function escapeValue(
        mixed $value,
        #[ExpectedValues(['string', 'int', 'bool', null])] ?string $fixedCast = null
    ): string|int|float {
        if (is_object($value)) {
            if ($value instanceof ObjectTransformable) {
                $value = $value->getDbValue();
            } else {
                $value = (string)$value;
            }
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_array($value)) {
            $arr = [];
            foreach ($value as $v) {
                $arr[] = $this->escapeValue($v, $fixedCast);
            }
            return "(" . implode(", ", $arr) . ")";
        }
        if ($fixedCast) {
            $value = QuickCast::to($value, $fixedCast);
        }
        if (is_float($value)) {
            return $value;
        }
        if (is_bool($value) || is_int($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return '"' . mysqli_real_escape_string($this->mysqli, $value) . '"';
        }
        throw new FatalError("Unsupported value for database parameters");
    }

    /**
     * Connect to database if not yet connected
     */
    public function connect(): void
    {
        // already connected
        if ($this->mysqli) {
            return;
        }
        $databaseConfig = Config::$dbConnections[$this->id];
        try {
            mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);
            $this->connectionConfig = [
                "host" => $databaseConfig['host'] ?? "localhost",
                "username" => $databaseConfig['username'],
                "password" => $databaseConfig['password'],
                "database" => $databaseConfig['database'],
                "port" => $databaseConfig['port'] ?: 3306,
                "socket" => $databaseConfig['socket'] ?? null,
            ];
            $this->mysqli = new mysqli(
                $this->connectionConfig['host'],
                $this->connectionConfig['username'],
                $this->connectionConfig['password'],
                $this->connectionConfig['database'],
                $this->connectionConfig['port'],
                $this->connectionConfig['socket'] ?: null
            );
        } catch (mysqli_sql_exception $e) {
            throw new FatalError($e->getMessage());
        }
        $this->mysqli->set_charset($databaseConfig['charset'] ?? 'utf8mb4');
    }

    /**
     * Execute an insert query
     * @param string $table
     * @param array $values
     * @param string $insertMethod Could be INSERT or REPLACE
     * @return bool
     */
    public function insert(string $table, array $values, string $insertMethod = "INSERT"): bool
    {
        $query = $insertMethod . " `$table` (";
        foreach ($values as $key => $value) {
            $query .= "`$key`, ";
        }
        $query = mb_substr($query, 0, -2) . ") VALUES (";
        foreach ($values as $value) {
            $query .= $this->escapeValue($value) . ", ";
        }
        $query = mb_substr($query, 0, -2) . ")";
        return $this->query($query);
    }

    /**
     * Execute a delete query
     * @param string $table
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return bool
     */
    public function delete(string $table, string $condition, ?array $conditionParameters = null): bool
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "DELETE FROM `$table` WHERE $condition";
        return $this->query($query);
    }

    /**
     * Execute an update query
     * @param string $table
     * @param array $values
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return bool
     */
    public function update(string $table, array $values, string $condition, ?array $conditionParameters = null): bool
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "UPDATE `$table` SET ";
        foreach ($values as $key => $value) {
            $query .= "`$key` = " . $this->escapeValue($value) . ", ";
        }
        $query = mb_substr($query, 0, -2) . " WHERE " . $condition;
        return $this->query($query);
    }

    /**
     * Execute the query
     * @param string $query
     * @param array|null $parameters
     * @return bool|mysqli_result
     */
    public function query(string $query, ?array $parameters = null): bool|mysqli_result
    {
        // replace php class names to real table names
        preg_match_all("~`(Framelix\\\\[a-zA-Z0-9_\\\\]+)`~", $query, $classNames);
        foreach ($classNames[0] as $key => $search) {
            $tableName = Storable::getTableName($classNames[1][$key]);
            $query = str_replace($search, '`' . $tableName . '`', $query);
        }
        $query = $this->replaceParameters($query, $parameters);
        return $this->queryRaw($query);
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
            // maybe its a legacy behaviour of some mysql drivers
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
     * Fetch the first value from the first row of the select result
     * @param string $query
     * @param array|null $parameters
     * @return string|null
     */
    public function fetchOne(string $query, ?array $parameters = null): ?string
    {
        $result = $this->fetchAssocOne($query, $parameters);
        if ($result) {
            return reset($result);
        }
        return null;
    }

    /**
     * Fetch the first column of each row of the select result
     * The second column, if exist, will be the key of the resulting array
     * The result will just key/value pairs instead of multidimensional array
     * @param string $query
     * @param array|null $parameters
     * @return array
     */
    public function fetchColumn(string $query, ?array $parameters = null): array
    {
        $fetch = $this->fetchArray($query, $parameters);
        if ($fetch && isset($fetch[0][1])) {
            return ArrayUtils::map($fetch, '0', '1');
        }
        return ArrayUtils::map($fetch, '0');
    }

    /**
     * Fetch the complete result of a select as bare array (numeric indexes)
     * @param string $query
     * @param array|null $parameters
     * @param int|null $limit If set, than stop when the given limit is reached
     * @return array[]
     */
    public function fetchArray(
        string $query,
        ?array $parameters = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $result = $this->query($query, $parameters);
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $fetch[] = $row;
            if (is_int($limit) && $limit <= count($fetch)) {
                break;
            }
        }
        return $fetch;
    }

    /**
     * Fetch the complete result of a select as an array with column names as keys
     * @param string $query
     * @param array|null $parameters
     * @param string|null $valueAsArrayIndex Use the value if the given key as array index (instead of numeric index)
     * @param int|null $limit If set, than stop when the given limit is reached
     * @return array[]
     */
    public function fetchAssoc(
        string $query,
        ?array $parameters = null,
        ?string $valueAsArrayIndex = null,
        ?int $limit = null
    ): array {
        $fetch = [];
        $result = $this->query($query, $parameters);
        while ($row = $result->fetch_assoc()) {
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
     * Fetch the first row of a select as an array with column names as keys
     * @param string $query
     * @param array|null $parameters
     * @return array|null
     */
    public function fetchAssocOne(string $query, ?array $parameters = null): ?array
    {
        $arr = $this->fetchAssoc($query, $parameters, null, 1);
        if ($arr) {
            return $arr[0];
        }
        return null;
    }

    /**
     * Get last insert id from last insert query
     * @return int
     */
    public function getLastInsertId(): int
    {
        return (int)mysqli_insert_id($this->mysqli);
    }

    /**
     * Get affected rows of last query
     * @return int
     */
    public function getAffectedRows(): int
    {
        return (int)mysqli_affected_rows($this->mysqli);
    }

    /**
     * Replace parameter placeholders in qiven array
     * @param string $str Placeholders are written in {} brackets
     * @param array|null $parameters
     * @return string
     */
    public function replaceParameters(string $str, ?array $parameters = null): string
    {
        if (!is_array($parameters)) {
            return $str;
        }
        foreach ($parameters as $key => $value) {
            $str = str_replace('{' . $key . '}', $this->escapeValue($value), $str);
        }
        return $str;
    }
}