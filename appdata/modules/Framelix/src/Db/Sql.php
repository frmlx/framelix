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

use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function mb_substr;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function reset;
use function str_replace;

abstract class Sql
{
    public const int TYPE_MYSQL = 1;
    public const int TYPE_SQLITE = 2;
    public const int TYPE_POSTGRES = 3;

    public static array $typeMap = [
        self::TYPE_MYSQL => ['class' => Mysql::class],
        self::TYPE_SQLITE => ['class' => Sqlite::class],
        self::TYPE_POSTGRES => ['class' => Postgres::class]
    ];

    /**
     * Instances
     * @var Mysql[]|Sqlite[]|Postgres[]
     */
    public static array $instances = [];

    /**
     * For debugging log executed queries into $this->executedQueries
     * @var bool
     */
    public static bool $logExecutedQueries = false;

    /**
     * The connection id for this instance
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
     * Is connected to database
     * @var bool
     */
    public bool $connected = false;

    /**
     * The start and end control characters to escape fields in the database
     * @var string
     */
    public string $quoteChars = "``";

    /**
     * Internal cache for some getters
     * @var array
     */
    protected array $cache = [];

    /**
     * Get instance for given id
     * Must be called from a class that extend from Sql
     * @param string $id
     * @param bool $connect If true, then connect before returning
     * @return Mysql|Sqlite|Postgres
     */
    public static function get(string $id = FRAMELIX_MODULE, bool $connect = true): Mysql|Sqlite|Postgres
    {
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }
        $config = Config::$sqlConnections[$id] ?? null;
        if (!isset($config)) {
            throw new FatalError('Sql connection with id "' . $id . '" do not exist');
        }
        /** @var Mysql|Sqlite|Postgres $instance */
        $instance = new self::$typeMap[$config['type']]['class']();
        $instance->id = $id;
        self::$instances[$id] = $instance;
        $instance->setConfig($config);
        if ($connect) {
            $instance->connect();
        }
        return $instance;
    }

    /**
     * Set class properties by given config values
     */
    abstract public function setConfig(array $configValues): void;

    /**
     * Connect to database
     */
    abstract public function connect(): void;

    /**
     * Execute the raw query without any framework modification
     * @param string $query
     * @return mixed
     */
    abstract public function queryRaw(string $query): mixed;

    /**
     * Get last insert id from last insert query
     * @return int
     */
    abstract public function getLastInsertId(): int;

    /**
     * Fetch the complete result of a select as bare array (numeric indexes)
     * @param string $query
     * @param array|null $parameters
     * @param int|null $limit If set, then stop when the given limit is reached
     * @return array[]
     */
    abstract public function fetchArray(
        string $query,
        ?array $parameters = null,
        ?int $limit = null
    ): array;

    /**
     * Fetch the complete result of a select as an array with column names as keys
     * @param string $query
     * @param array|null $parameters
     * @param string|null $valueAsArrayIndex Use the value if the given key as array index (instead of numeric index)
     * @param int|null $limit If set, then stop when the given limit is reached
     * @return array[]
     */
    abstract public function fetchAssoc(
        string $query,
        ?array $parameters = null,
        ?string $valueAsArrayIndex = null,
        ?int $limit = null
    ): array;

    /**
     * Disconnect from database
     */
    public function disconnect(): void
    {
        $this->cache = [];
        $this->connected = false;
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
        $rangeStart = DateTime::create($dateStart ?: "0000-01-01");
        $rangeEnd = DateTime::create($dateEnd ?: "9999-12-31");
        if (!$rangeStart) {
            $rangeStart = DateTime::create("0000-01-01");
        }
        if (!$rangeEnd) {
            $rangeEnd = DateTime::create("9999-12-31");
        }
        $rangeStartDateTime = $this->escapeValue($rangeStart . " 00:00:00");
        $rangeEndDateTime = $this->escapeValue($rangeEnd . " 23:59:59");
        // this condition should NOT use functions for the DB fields, as they cannot use indexes later
        // it's way more performant to check the untouched db fields against a static date and datetime value instead of using DATE() mysql function
        return "($dbFieldEnd IS NULL || $rangeStartDateTime <= $dbFieldEnd) && ($dbFieldStart IS NULL || $dbFieldStart <= $rangeEndDateTime)";
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
        $rangeStart = DateTime::create($rangeStart);
        $rangeEnd = DateTime::create($rangeEnd);
        switch ($compareMethod) {
            case 'month':
                $rangeStart?->setDayOfMonth(1);
                $rangeEnd?->setDayOfMonth(-1);
                break;
            case 'year':
                $rangeStart?->setMonth(1)?->setDayOfMonth(1);
                $rangeEnd?->setMonth(12)?->setDayOfMonth(-1);
                break;
        }
        // this conditions should NOT use functions for the DB field, as they cannot use indexes later
        // it's way more performant to check the untouched db field against a static date and datetime value instead of using DATE() mysql function
        $condition = [];
        if ($rangeStart) {
            $condition[] = "($dbField >= " . $this->escapeValue($rangeStart . " 00:00:00") . ")";
        }
        if ($rangeEnd) {
            $condition[] = "($dbField <= " . $this->escapeValue($rangeEnd . " 23:59:59") . ")";
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
        $rangeStart = DateTime::create($date);
        $rangeEnd = DateTime::create($date);
        switch ($compareMethod) {
            case 'month':
                $rangeStart->setDayOfMonth(1);
                $rangeEnd->setDayOfMonth(-1);
                break;
            case 'year':
                $rangeStart->setMonth(1)->setDayOfMonth(1);
                $rangeEnd->setMonth(12)->setDayOfMonth(-1);
                break;
        }
        return self::getConditionDateRangeOverlaps($rangeStart, $rangeEnd, $dbFieldStart, $dbFieldEnd);
    }

    /**
     * Escape any value for database usage
     * @param mixed $value
     * @param string|null $fixedCast Force a cast to string|int|bool when escaping to fix issues when different type to db will result in loss performance
     *  Attention: NULL will stay as is, array values (each value separately) will be cast as well
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
        if (is_float($value) || is_int($value)) {
            return $this->escapeNumber($value);
        }
        if (is_bool($value)) {
            return $this->escapeBool($value);
        }
        if (is_string($value)) {
            return $this->escapeString($value);
        }
        throw new FatalError("Unsupported value for database parameters");
    }

    abstract public function escapeString(string $value): mixed;

    public function escapeNumber(float|int $value): mixed
    {
        return $value;
    }

    public function escapeBool(bool $value): mixed
    {
        return (int)$value;
    }

    /**
     * Quote identifiers. In Mysql the parts will be concated with . and each part is enclosed with `
     * @param string ...$parts
     * @return string
     */
    public function quoteIdentifier(string ...$parts): string
    {
        foreach ($parts as $key => $part) {
            $parts[$key] = $this->quoteChars[0] . $part . $this->quoteChars[1];
        }
        return implode(".", $parts);
    }

    /**
     * Execute an insert query
     * @param string $table
     * @param array $values
     * @param string $insertMethod
     * @return mixed The sql result
     */
    public function insert(
        string $table,
        array $values,
        #[ExpectedValues(values: ['INSERT', 'REPLACE'])] string $insertMethod = "INSERT"
    ): mixed {
        $query = $insertMethod . " INTO " . $this->quoteIdentifier($table) . " (";
        foreach ($values as $key => $value) {
            $query .= $this->quoteIdentifier($key) . ", ";
        }
        $query = mb_substr($query, 0, -2) . ") VALUES (";
        foreach ($values as $key => $value) {
            $query .= "{" . $key . "}, ";
        }
        $query = mb_substr($query, 0, -2) . ")";
        return $this->query($query, $values);
    }

    /**
     * Execute an update query
     * @param string $table
     * @param array $values
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return mixed The sql result
     */
    public function update(string $table, array $values, string $condition, ?array $conditionParameters = null): mixed
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "UPDATE " . $this->quoteIdentifier($table) . " SET ";
        foreach ($values as $key => $value) {
            $query .= $this->quoteIdentifier($key) . " = {" . $key . "}, ";
        }
        $query = mb_substr($query, 0, -2) . " WHERE " . $condition;
        return $this->query($query, $values);
    }

    /**
     * Execute a delete query
     * @param string $table
     * @param string $condition The WHERE condition, need to be set, set to 1 if you want all rows affected
     * @param array|null $conditionParameters
     * @return mixed The sql result
     */
    public function delete(string $table, string $condition, ?array $conditionParameters = null): mixed
    {
        $condition = $this->replaceParameters($condition, $conditionParameters);
        $query = "DELETE FROM " . $this->quoteIdentifier($table) . " WHERE $condition";
        return $this->query($query);
    }

    /**
     * Does replace some framework specials inside given query
     * It searches for class names and replace it to table names
     * It searches framelix default quote chars and replace it with db specific quote chars
     * It searches && and || and replaced with AND OR because that is SQL standard
     * @param string $query
     * @return string
     */
    public function prepareQuery(string $query): string
    {
        // replace && and ||
        $query = str_replace(['&&', '||'], ['AND', 'OR'], $query);

        // replace framelix default quote identifiers,  which are `, with sql specific quote identifiers, which not always be `
        if ($this->quoteChars[0] !== "`") {
            $query = preg_replace("~`([a-z0-9-_]+)`~i", $this->quoteChars[0] . "$1" . $this->quoteChars[1], $query);
        }

        // replace php class names to real table names
        preg_match_all(
            "~" .
            preg_quote($this->quoteChars[0]) .
            "(Framelix\\\\[a-zA-Z0-9_\\\\]+)" .
            preg_quote($this->quoteChars[1]) .
            "~",
            $query,
            $classNames
        );
        foreach ($classNames[0] as $key => $search) {
            $tableName = Storable::getTableName($classNames[1][$key]);
            $query = str_replace($search, $this->quoteIdentifier($tableName), $query);
        }
        return $query;
    }

    /**
     * Execute the query
     * @param string $query
     * @param array|null $parameters
     * @return mixed The sql result
     */
    public function query(string $query, ?array $parameters = null): mixed
    {
        $query = $this->replaceParameters($this->prepareQuery($query), $parameters);
        return $this->queryRaw($query);
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
     * The second column, if exists, will be the key of the resulting array
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