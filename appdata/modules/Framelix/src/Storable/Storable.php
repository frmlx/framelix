<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JsonSerializable;
use ReflectionClass;

use function array_pop;
use function array_reverse;
use function call_user_func;
use function call_user_func_array;
use function class_exists;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function reset;
use function substr;
use function trim;

/**
 * Base Storable
 * @property int|null $id
 */
abstract class Storable implements JsonSerializable, ObjectTransformable
{

    /**
     * Deactivating the prefetch behaviour globally if you need to here
     * @var bool
     */
    public static bool $prefetchEnabled = true;

    /**
     * Internal db cache
     * @var array
     */
    private static array $dbCache = [];

    /**
     * Internal schema cache
     * @var array
     */
    private static array $schemaCache = [];

    /**
     * Internal schema table cache
     * @var array
     */
    private static array $schemaTableCache = [];

    /**
     * The db connection id for this storable
     * @var string
     */
    public string $connectionId;

    /**
     * Property cache
     * @var array
     */
    protected array $propertyCache = [];


    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action == 'deleteStorable') {
            $storable = Storable::getById(Request::getGet('id'), Request::getGet('connectionId'));
            $result = 'Storable not found';
            if ($storable) {
                $storable->delete();
                $result = true;
            }
            if ($redirect = Request::getGet('redirect')) {
                Url::create($redirect)->redirect();
            }
            $jsCall->result = $result;
        }
    }

    /**
     * Get connection that is default responsible for the called storable
     * Default is always FRAMELIX_MODULE if not overriden in setupStorableScheme
     * @return string
     */
    final public static function getConnectionId(): string
    {
        return self::getStorableSchema(static::class)->connectionId;
    }

    /**
     * Get a single storable by id and return an empty instance if not found
     * @param string|int|null $id
     * @param string|null $connectionId Database connection id to use
     * @param bool|null $withChilds See self::getByCondition
     * @param bool|null $readable If set, only fetch id that match this readable status
     * @return static
     * @see self::getByCondition
     */
    final public static function getByIdOrNew(
        mixed $id,
        ?string $connectionId = null,
        ?bool $withChilds = null,
        ?bool $readable = true
    ): static {
        return self::getById($id, $connectionId, $withChilds, $readable) ?? new static();
    }

    /**
     * Get a single storable by id
     * @param string|int|null $id
     * @param string|null $connectionId Override database connection id to use
     * @param bool|null $withChilds See self::getByCondition
     * @param bool|null $readable If set, only fetch id that match this readable status
     * @return static|null
     * @see self::getByCondition
     */
    final public static function getById(
        mixed $id,
        ?string $connectionId = null,
        ?bool $withChilds = null,
        ?bool $readable = true
    ): ?static {
        if (!$id || !is_numeric($id)) {
            return null;
        }
        return static::getByIds([$id], $connectionId, $withChilds, $readable)[$id] ?? null;
    }

    /**
     * Get storables by given array of ids
     * @param array|null $ids
     * @param string|null $connectionId Override database connection id to use
     * @param bool|null $withChilds See self::getByCondition
     * @param bool|null $readable If set, only fetch id that match this readable status
     * @return static[]
     * @see self::getByCondition
     */
    final public static function getByIds(
        ?array $ids,
        ?string $connectionId = null,
        ?bool $withChilds = null,
        ?bool $readable = true
    ): array {
        if (!$ids) {
            return [];
        }
        $connectionId = $connectionId ?? static::getConnectionId();
        $storables = [];
        $cachedStorables = self::$dbCache[$connectionId] ?? [];
        $idsRest = $ids;
        $classNow = static::class;
        // abstract classes can not be directly fetched from the database, as they not exist
        // we consider abstract class fetch to want to fetch all its childs that met the condition
        $storableSchema = static::getStorableSchema();
        if ($withChilds === null && $storableSchema->abstract) {
            $withChilds = true;
        }
        foreach ($ids as $key => $id) {
            if (!$id || !is_numeric($id)) {
                unset($idsRest[$key]);
                continue;
            }
            $id = (int)$id;
            $ids[$key] = $id;
            $idsRest[$key] = $id;
            if (isset($cachedStorables[$id])) {
                /** @var Storable $cachedStorable */
                $cachedStorable = $cachedStorables[$id];
                // only return when same type or instance of when with childs is enabled
                if (!$withChilds || ($cachedStorables[$id] instanceof $classNow)) {
                    if (is_bool($readable) && $readable !== $cachedStorable->isReadable()) {
                        continue;
                    }
                    $storables[$id] = $cachedStorables[$id];
                }
                // just remove from rest when exist in cache, even when we do not return all of them
                unset($idsRest[$key]);
            }
        }
        // ids to fetch from database
        if ($idsRest) {
            $storables = ArrayUtils::merge(
                $storables,
                static::getByCondition(
                    'id IN {0}',
                    [$idsRest],
                    connectionId: $connectionId,
                    withChilds: $withChilds,
                    readable: $readable
                )
            );
        }
        // keep original ids sort
        $returnArr = [];
        foreach ($ids as $id) {
            if (isset($storables[$id])) {
                $returnArr[$id] = $storables[$id];
            }
        }
        return $returnArr;
    }

    /**
     * Get a single storable by a condition
     * @param string|null $condition Basically the WHERE condition
     * @param array|null $parameters Parameters to replace in $condition
     * @param array|string|null $sort Single or multiple database properties to sort by, +propName will sort ASC, -propName will sort DESC
     * @param int|null $offset Offset (see SQL docs) from result, Limit (see SQL docs) here is automatically 1
     * @param string|null $connectionId Override database connection id to use
     * @param bool|null $withChilds See self::getByCondition
     * @param bool|null $readable If set, only fetch id that match this readable status
     * @return static|null
     * @see self::getByCondition
     */
    final public static function getByConditionOne(
        ?string $condition = null,
        ?array $parameters = null,
        array|string|null $sort = null,
        ?int $offset = null,
        ?string $connectionId = null,
        ?bool $withChilds = null,
        ?bool $readable = true
    ): ?static {
        $arr = static::getByCondition(
            $condition,
            $parameters,
            $sort,
            1,
            $offset,
            $connectionId,
            $withChilds,
            $readable
        );
        if ($arr) {
            return reset($arr);
        }
        return null;
    }

    /**
     * Get array of storables by a condition
     * @param string|null $condition Basically the WHERE condition
     * @param array|null $parameters Parameters to replace in $condition
     * @param array|string|null $sort Single or multiple database properties to sort by, +propName will sort ASC, -propName will sort DESC
     * @param int|null $limit Limit (see SQL docs) from result
     * @param int|null $offset Offset (see SQL docs) from result
     * @param string|null $connectionId Override database connection id to use
     * @param bool|null $withChilds Include all storables the inherited from called class, otherwise only the called class will be fetched
     *  If null is given, the system will automatically detect abstract class calls as true
     *  If bool is given, no automatic detection is enabled
     * @param bool|null $readable If set, only fetch id that match this readable status
     * @return static[]
     */
    final public static function getByCondition(
        ?string $condition = null,
        ?array $parameters = null,
        array|string|null $sort = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $connectionId = null,
        ?bool $withChilds = null,
        ?bool $readable = true
    ): array {
        $storableSchema = static::getStorableSchema();
        $db = Sql::get($connectionId ?? static::getConnectionId());
        // abstract classes can not be directly fetched from the database, as they not exist
        // we consider abstract class fetch to want to fetch all its childs that met the condition
        if ($withChilds === null && $storableSchema->abstract) {
            $withChilds = true;
        }
        if ($withChilds) {
            self::fetchSchemaTable($db->id);
            /** @var static[] $childClasses */
            $childClasses = [];
            // if self class is not abstract, add it to the list of classes
            if (!$storableSchema->abstract) {
                $childClasses[] = static::class;
            }
            $arr = [];
            foreach (self::$schemaTableCache[$db->id] as $class => $row) {
                if (in_array(static::class, $row['parents'])) {
                    $childClasses[] = $class;
                }
            }
            foreach ($childClasses as $childClass) {
                if (!class_exists($childClass)) {
                    continue;
                }
                $arr = ArrayUtils::merge(
                    $arr,
                    $childClass::getByCondition(
                        $condition,
                        $parameters,
                        $sort,
                        $limit,
                        $offset,
                        $connectionId,
                        false,
                        $readable
                    )
                );
            }
            return $arr;
        }
        $properties = [];
        foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
            // ignore lazy fetch, it is fetched when actually calling the property
            if ($storableSchemaProperty->lazyFetch) {
                continue;
            }
            $properties[] = "`t0`.`$propertyName`";
        }
        if ($sort) {
            if (!is_array($sort)) {
                $sort = [$sort];
            }
        }
        $query = "SELECT " . implode(
                ", ",
                $properties
            ) . "\nFROM `$storableSchema->tableName` as t0\n";


        $querySearch = "";
        if ($condition) {
            $querySearch .= $condition;
        }
        if ($sort) {
            $querySearch .= implode(", ", $sort);
        }
        // depth joins
        // find all conditions that are concated with a dot
        // each part represent the nested storable reference from the storable
        // so example: 'createUser.email IS NULL' will find each storable where its createUser email is null
        // this will get automatically joined by this technique
        $depthJoinSearchReplace = [];
        if ($querySearch) {
            preg_match_all("~([a-z0-9_]+\.[^\s]+)~i", $querySearch, $depthMatches);
            if ($depthMatches[0]) {
                $uniqueMatches = [];
                foreach ($depthMatches[0] as $value) {
                    $uniqueMatches[$value] = $value;
                }
                $tableAliasToDepthPath = [];
                $joinCount = 1;
                foreach ($uniqueMatches as $uniqueMatch) {
                    $parts = explode(".", $uniqueMatch);
                    $partStorableSchema = $storableSchema;
                    $depthPath = "";
                    $prevDepthPath = "";
                    $lastPart = array_pop($parts);
                    $aliasTableName = null;
                    foreach ($parts as $partPropertyName) {
                        $depthPath .= $partPropertyName . "-";
                        $partStorableSchemaProperty = $partStorableSchema->properties[$partPropertyName] ?? null;
                        // skip if property has not been found
                        if (!$partStorableSchemaProperty || !$partStorableSchemaProperty->storableClass) {
                            $aliasTableName = null;
                            continue;
                        }
                        $partStorableSchema = Storable::getStorableSchema($partStorableSchemaProperty->storableClass);
                        $prevAliasTableName = !$prevDepthPath ? "t0" : $tableAliasToDepthPath[$prevDepthPath];
                        $aliasTableName = $tableAliasToDepthPath[$depthPath] ?? null;
                        if (!$aliasTableName) {
                            $aliasTableName = "t" . $joinCount;
                            $query .= "LEFT JOIN `$partStorableSchema->tableName` as `$aliasTableName` ON ";
                            $query .=
                                $db->quoteIdentifier($aliasTableName, "id") . " = " .
                                $db->quoteIdentifier($prevAliasTableName, $partStorableSchemaProperty->name);
                            $query .= "\n";
                            $tableAliasToDepthPath[$depthPath] = $aliasTableName;
                            $joinCount++;
                        }
                        $prevDepthPath = $depthPath;
                    }
                    if ($aliasTableName) {
                        $depthJoinSearchReplace[$uniqueMatch] = "`$aliasTableName`.`$lastPart`";
                    }
                }
            }
        }
        if ($condition) {
            $query .= "WHERE $condition\n";
        }
        $query .= "GROUP BY t0.id\n";
        if ($sort) {
            $query .= "ORDER BY ";
            foreach ($sort as $sortProperty) {
                if ($sortProperty[0] !== "-" && $sortProperty[0] !== "+") {
                    throw new FatalError("Sort properties must begin with -/+ to indicate sort direction");
                }
                $query .= "`" . substr($sortProperty, 1) . "` " . ($sortProperty[0] === "+" ? "ASC" : "DESC") . ", ";
            }
            $query = trim($query, ", ") . "\n";
        }
        if (is_int($limit)) {
            $query .= "LIMIT $limit\n";
        }
        if (is_int($offset)) {
            $query .= "OFFSET $limit\n";
        }
        if ($depthJoinSearchReplace) {
            foreach ($depthJoinSearchReplace as $search => $replace) {
                $query = preg_replace(
                    "~(^|\s+|\()" . preg_quote($search, "~") . "(\s+|$|\))~i",
                    "$1$replace$2",
                    $query
                );
            }
        }
        $query = $db->replaceParameters($query, $parameters);
        $dbFetch = $db->fetchAssoc($query);
        $storables = [];
        foreach ($dbFetch as $row) {
            // re-use already cached storables
            if (isset(self::$dbCache[$db->id][$row['id']])) {
                /** @var Storable $storable */
                $storable = self::$dbCache[$db->id][$row['id']];
                if (is_bool($readable) && $readable !== $storable->isReadable()) {
                    continue;
                }
                $storables[$storable->id] = $storable;
            } else {
                $storable = new static();
                $storable->connectionId = $db->id;
                foreach ($row as $key => $value) {
                    self::$dbCache[$db->id][$storable->id] = $storable;
                    if (is_bool($readable) && $readable !== $storable->isReadable()) {
                        continue;
                    }
                    $storable->propertyCache['dbvalue'][$key] = $value;
                    $storables[$storable->id] = $storable;
                }
            }
        }
        return $storables;
    }

    /**
     * Delete multiple storables
     * @param self[]|self|null $storables
     * @param bool $force Force deletion even if isDeletable() is false
     */
    final public static function deleteMultiple(mixed $storables, bool $force = false): void
    {
        if ($storables instanceof Storable) {
            $storables->delete($force);
            return;
        }
        if (!is_array($storables)) {
            return;
        }
        foreach ($storables as $storable) {
            // skip everything that is no proper storable
            if (!($storable instanceof Storable) || !$storable->id) {
                continue;
            }
            $storable->delete($force);
        }
    }

    /**
     * Get the database table name to this storable
     * @param string|Storable $storable
     * @return string
     */
    final public static function getTableName(string|Storable $storable): string
    {
        return self::getStorableSchema($storable)->tableName;
    }

    /**
     * Get storable scehama for given class
     * @param string|Storable|null $storable If null than is called class
     * @return StorableSchema
     */
    final public static function getStorableSchema(string|Storable|null $storable = null): StorableSchema
    {
        if (!$storable) {
            $storable = static::class;
        }
        if (is_object($storable)) {
            $storable = get_class($storable);
        }
        $cacheKey = "schema-" . $storable;
        if (isset(self::$schemaCache[$cacheKey])) {
            return self::$schemaCache[$cacheKey];
        }
        $reflectionClass = new ReflectionClass($storable);
        $parent = $reflectionClass;
        $parentReflections = [];
        while ($parent = $parent->getParentClass()) {
            $parentReflections[] = $parent;
        }
        $parentReflections = array_reverse($parentReflections);
        $storableSchema = new StorableSchema($storable);
        foreach ($parentReflections as $parentReflection) {
            $storableSchema->mergeParent(self::getStorableSchema($parentReflection->getName()));
        }
        $storableSchema->parseClassData();
        call_user_func([$storable, "setupStorableSchema"], $storableSchema);
        self::$schemaCache[$cacheKey] = $storableSchema;
        return $storableSchema;
    }

    /**
     * Get storable schema property for given class property
     * @param string|Storable $storable
     * @param string $property
     * @return StorableSchemaProperty|null
     */
    final public static function getStorableSchemaProperty(
        string|Storable $storable,
        string $property
    ): ?StorableSchemaProperty {
        return self::getStorableSchema($storable)->properties[$property] ?? null;
    }

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
    }

    /**
     * Fetch the whole schema table if not yet fetched
     */
    private static function fetchSchemaTable(string $connectionId): void
    {
        if (!isset(self::$schemaTableCache[$connectionId])) {
            self::$schemaTableCache[$connectionId] = [];
            $fetch = Sql::get($connectionId)->fetchAssoc("SELECT * FROM " . StorableSchema::SCHEMA_TABLE);
            foreach ($fetch as $row) {
                self::$schemaTableCache[$connectionId][$row['storableClass']] = [
                    'id' => (int)$row['id'],
                    'parents' => []
                ];
                self::$schemaTableCache[$connectionId][$row['storableClass']]['parents'] = JsonUtils::decode(
                    $row['storableClassParents']
                );
            }
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connectionId = self::getConnectionId();
    }

    /**
     * On clone
     */
    public function __clone(): void
    {
        throw new FatalError('Native clone isnt supported - Use ->clone() on the storable');
    }

    /**
     * To string
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }

    /**
     * Get property
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $label = "Property " . get_class($this) . "->$name";
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $name);
        if (!$storableSchemaProperty) {
            throw new FatalError("$label not exist in storable ");
        }
        // a complete new storable does not have anything set yet
        if (!$this->propertyCache) {
            return null;
        }
        if (ArrayUtils::keyExists($this->propertyCache, "phpvalue[$name]")) {
            return $this->propertyCache["phpvalue"][$name];
        }
        // lazy load if required
        if (
            $storableSchemaProperty->lazyFetch &&
            $this->id &&
            !ArrayUtils::keyExists($this->propertyCache, "dbvalue[$name]")
        ) {
            $db = $this->getDb();
            $this->propertyCache["dbvalue"][$name] = $db->fetchOne(
                "SELECT `$storableSchemaProperty->name`
                FROM `" . $this::class . "`
                WHERE id = $this"
            );
        }
        $realValue = $this->getConvertedDbValue($name, $this->propertyCache["dbvalue"][$name] ?? null);
        $this->propertyCache["phpvalue"][$name] = $realValue;
        return $realValue;
    }

    /**
     * Set property
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $name);
        $label = "Property " . get_class($this) . "->$name";
        // native property support
        if (!$storableSchemaProperty) {
            throw new FatalError("$label not exist in storable");
        }
        // check for correct types
        if ($value !== null) {
            if ($storableSchemaProperty->storableClass && !($value instanceof $storableSchemaProperty->storableClass)) {
                throw new FatalError(
                    "$label need to be an instance of " . $storableSchemaProperty->storableClass
                );
            }
            if ($storableSchemaProperty->storableInterface && !($value instanceof $storableSchemaProperty->storableInterface)) {
                throw new FatalError(
                    "$label need to be an instance of " . $storableSchemaProperty->storableInterface
                );
            }
            switch ($storableSchemaProperty->internalType) {
                case "bool":
                    if (!is_bool($value)) {
                        throw new FatalError("$label need to be a boolean value");
                    }
                    break;
                case "int":
                    if (!is_int($value)) {
                        throw new FatalError("$label need to be a integer value");
                    }
                    break;
                case "float":
                    if (!is_float($value)) {
                        throw new FatalError("$label need to be a float value");
                    }
                    break;
                case "string":
                    if (!is_string($value)) {
                        throw new FatalError("$label need to be a string value");
                    }
                    break;
            }
        }
        $this->propertyCache['phpvalue'][$name] = $value;
        $this->propertyCache['modified'][$name] = true;
    }

    /**
     * Clone self without an id
     * @return static
     */
    public function clone(): static
    {
        $obj = new static();
        $obj->connectionId = $this->connectionId;
        foreach (self::getStorableSchema($this)->properties as $propertyName => $property) {
            if ($propertyName === 'id') {
                continue;
            }
            $obj->{$property->name} = $this->{$property->name};
        }
        return $obj;
    }

    /**
     * Get database connection
     * @return Sql
     */
    final public function getDb(): Sql
    {
        return Sql::get($this->connectionId);
    }

    /**
     * Get the original db value of given property
     * This is the value that has been originally fetched from database if object comes from database, even if you have changed the property in the meantime
     * For new storables, this returns always null
     * @param string $propertyName
     * @return string|null
     */
    final public function getOriginalDbValueForProperty(string $propertyName): ?string
    {
        return $this->propertyCache['dbvalue'][$propertyName] ?? null;
    }

    /**
     * Get the database value the is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the given property value
     * So if you have modified a value of a property after fetching from database, then this value is the modified one
     * @param string $propertyName
     * @return mixed
     */
    final public function getNewDbValueForProperty(string $propertyName): mixed
    {
        $storableSchemaProperty = self::getStorableSchemaProperty($this, $propertyName);
        if (!$storableSchemaProperty) {
            return null;
        }
        $phpValue = $this->{$propertyName};
        if ($phpValue === null) {
            return null;
        }
        if ($storableSchemaProperty->storableClass || $storableSchemaProperty->storableInterface) {
            if ($phpValue instanceof ObjectTransformable) {
                return $phpValue->getDbValue();
            }
        }
        return match ($storableSchemaProperty->internalType) {
            "bool" => $phpValue ? 1 : 0,
            "mixed" => JsonUtils::encode($phpValue),
            default => $phpValue
        };
    }

    /**
     * Has a property been modified and not yet saved in database
     * @param string $propertyName
     * @return bool
     */
    final public function isPropertyModified(string $propertyName): bool
    {
        return $this->propertyCache['modified'][$propertyName] ?? false;
    }

    /**
     * Store into database
     * @param bool $force Force store even if isEditable() is false
     */
    public function store(bool $force = false): void
    {
        if (!$force && !$this->isEditable()) {
            throw new FatalError(
                "Storable #" . $this . " (" . $this->getRawTextString() . ") is not editable"
            );
        }
        $storableSchema = Storable::getStorableSchema($this);
        $storeValues = [];
        foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
            // skip untouched properties on updates
            if (
                $this->id &&
                (
                    !$this->isPropertyModified($propertyName) ||
                    !ArrayUtils::keyExists($this->propertyCache, 'phpvalue[' . $propertyName . ']')
                )
            ) {
                continue;
            }
            $finalDatabaseValue = $this->getNewDbValueForProperty($propertyName);
            // optional check
            if (!$storableSchemaProperty->optional && $finalDatabaseValue === null && $propertyName !== 'id') {
                $propertyValue = $this->{$propertyName};
                if ($propertyValue !== null) {
                    throw new FatalError(
                        "Property " . get_class(
                            $this
                        ) . "->$propertyName is set with an unstored Storable. Store this reference Storable first, so it does have property storable id to reference with."
                    );
                }
                throw new FatalError(
                    "Property " . get_class(
                        $this
                    ) . "->$propertyName is null but must be set (is not optional/nullable)"
                );
            }

            $storeValues[$propertyName] = $finalDatabaseValue;
        }
        // nothing changed, nothing to from here
        if (!$storeValues) {
            return;
        }
        $class = get_class($this);
        $db = $this->getDb();
        // get next available id from database
        $existingId = $this->id;
        if (!$existingId) {
            self::fetchSchemaTable($this->connectionId);
            $storableClassId = self::$schemaTableCache[$this->connectionId][$class]['id'];
            $db->insert(StorableSchema::ID_TABLE, ['storableId' => $storableClassId]);
            $this->id = $db->getLastInsertId();
            $storeValues["id"] = $this->id;
        }
        if (!$existingId) {
            $db->insert($storableSchema->tableName, $storeValues);
        } else {
            $db->update($storableSchema->tableName, $storeValues, "id = $existingId");
        }
        // unset all modified flags after stored in database
        unset($this->propertyCache['modified']);
        self::$dbCache[$this->connectionId][$this->id] = $this;
        $this->onDatabaseUpdated();
        // create system event logs
        $logCategory = $existingId ? SystemEventLog::CATEGORY_STORABLE_UPDATED : SystemEventLog::CATEGORY_STORABLE_CREATED;
        if (!($this instanceof SystemEventLog) && (Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
            SystemEventLog::create(
                $logCategory,
                null,
                ['id' => $this->id, 'connectionId' => $db->id, 'info' => $this->getRawTextString()],
                $db->id
            );
        }
    }

    /**
     * Delete from database
     * @param bool $force Force deletion even if isDeletable() is false
     */
    public function delete(bool $force = false): void
    {
        if (!$this->id) {
            throw new FatalError(
                "Cannot delete new storable that is not yet saved in database (" . get_class($this) . ")"
            );
        }
        if (!$force && !$this->isDeletable()) {
            throw new FatalError(
                "Storable #" . $this . " (" . $this->getRawTextString() . ") is not deletable"
            );
        }
        $db = $this->getDb();
        $storableSchema = Storable::getStorableSchema($this);
        $db->delete($storableSchema->tableName, "id = " . $this->id);
        $db->delete(StorableSchema::ID_TABLE, "id = " . $this->id);
        $id = $this->id;
        $textString = $this->getRawTextString();
        unset(self::$dbCache[$this->connectionId][$this->id]);
        $this->id = null;
        $this->onDatabaseUpdated();
        // create system event logs
        $logCategory = SystemEventLog::CATEGORY_STORABLE_DELETED;
        if (!($this instanceof SystemEventLog) && (Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
            SystemEventLog::create(
                $logCategory,
                null,
                ['id' => $id, 'connectionId' => $db->id, 'info' => $textString],
                $db->id
            );
        }
    }

    /**
     * Get the database id
     * @return int|null
     */
    final public function getDbValue(): ?int
    {
        return $this->id;
    }

    /**
     * Get a string to be used to display in a html page
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this->getRawTextString();
    }

    /**
     * Get a string to be used to display explicitely in a html table
     * @return string
     */
    public function getHtmlTableValue(): mixed
    {
        return $this->getHtmlString();
    }

    /**
     * Get raw text representation without any formatting
     * @return string
     */
    public function getRawTextString(): string
    {
        return "ID: " . $this . "|Class:" . get_class($this);
    }

    /**
     * Get the value that is internally used to do sorting on
     * @return string
     */
    public function getSortableValue(): string
    {
        return $this->getRawTextString();
    }

    public function jsonSerialize(): ?int
    {
        return $this->id;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isEditable(): bool
    {
        return $this->isReadable();
    }

    public function isDeletable(): bool
    {
        return $this->isEditable();
    }

    /**
     * Returns the url that is used in tables and other functions, to link to a details page (Usually where the storable can be edited)
     * @return Url|null
     */
    public function getDetailsUrl(): ?Url
    {
        return null;
    }

    final public function getDeleteUrl(string|Url|null $redirectToUrlAfterDelete = null): ?string
    {
        if (!$this->isDeletable()) {
            return null;
        }
        return JsCall::getUrl(
            __CLASS__,
            'deleteStorable',
            ['id' => $this->id, 'connectionId' => $this->connectionId, "redirect" => $redirectToUrlAfterDelete]
        );
    }

    /**
     * This function is called when the database has been updated after a store() or delete() call
     * You can hook into that by override this method
     */
    protected function onDatabaseUpdated(): void
    {
    }

    /**
     * Get the converted value that takes original database value and converts it to the final type
     * @param string $propertyName
     * @param mixed $dbValue
     * @return mixed
     */
    private function getConvertedDbValue(string $propertyName, mixed $dbValue): mixed
    {
        if ($dbValue === null) {
            return null;
        }
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $propertyName);
        $referenceStorableConnectionId = $storableSchemaProperty->connectionId === "_parent" ? $this->connectionId : $storableSchemaProperty->connectionId;
        $prefetchEnabled = self::$prefetchEnabled && $storableSchemaProperty->prefetchReferenceStorable;
        if ($prefetchEnabled) {
            /** @var Storable[] $cachedStorables */
            $cachedStorables = self::$dbCache[$this->connectionId] ?? null;
            foreach ($cachedStorables as $key => $cachedStorable) {
                if (!($cachedStorable instanceof $this)) {
                    unset($cachedStorables[$key]);
                }
            }
        }
        if ($storableSchemaProperty->storableInterface) {
            return call_user_func_array([$storableSchemaProperty->storableInterface, 'createFromDbValue'], [$dbValue]);
        } elseif ($storableSchemaProperty->storableClass) {
            if ($prefetchEnabled) {
                if ($cachedStorables) {
                    $fetchReferenceIds = [];
                    $count = 0;
                    foreach ($cachedStorables as $cachedStorable) {
                        $referenceId = $cachedStorable->getOriginalDbValueForProperty($propertyName);
                        // if this was already in a previous prefetch cycle
                        if (ArrayUtils::keyExists($cachedStorable->propertyCache, "phpvalue[$propertyName]")) {
                            continue;
                        }
                        if ($referenceId) {
                            $fetchReferenceIds[$referenceId] = $referenceId;
                            $count++;
                            if ($count >= $storableSchemaProperty->prefetchLimit) {
                                break;
                            }
                        }
                    }
                    // do not use call_user_func_array here, as a PHP bug prevent static::class to resolve properly
                    /** @var Storable $class */
                    $class = $storableSchemaProperty->storableClass;
                    $referenceStorables = $class::getByIds($fetchReferenceIds, $referenceStorableConnectionId);

                    foreach ($cachedStorables as $cachedStorable) {
                        $referenceId = $cachedStorable->getOriginalDbValueForProperty($propertyName);
                        if ($referenceId && isset($fetchReferenceIds[$referenceId])) {
                            $cachedStorable->propertyCache['phpvalue'][$propertyName] = $referenceStorables[$referenceId] ?? null;
                        }
                    }
                }
                return $this->propertyCache['phpvalue'][$propertyName] ?? null;
            }
            return call_user_func_array(
                [$storableSchemaProperty->storableClass, "getById"],
                ['id' => $dbValue, 'connectionId' => $referenceStorableConnectionId]
            );
        } else {
            return match ($storableSchemaProperty->internalType) {
                "bool" => (bool)$dbValue,
                "int" => (int)$dbValue,
                "float" => (float)$dbValue,
                "mixed" => JsonUtils::decode($dbValue),
                default => $dbValue
            };
        }
    }
}