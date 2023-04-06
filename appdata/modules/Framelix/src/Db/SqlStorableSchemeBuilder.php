<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function array_keys;
use function array_reverse;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function is_callable;
use function is_int;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * Sql scheme builder for storables
 * Automatically updates/create/modifies table schema in the database
 */
class SqlStorableSchemeBuilder
{
    public const MYSQL_DEFAULT_COLLATION = 'utf8mb4_unicode_ci';
    public const MYSQL_DEFAULT_ENGINE = 'InnoDB';

    public function __construct(public Sql $db)
    {
    }

    /**
     * Execute given builder queries
     * @param array $queries
     * @return void
     */
    public function executeQueries(array $queries): void
    {
        foreach ($queries as $row) {
            if ($row['ignoreErrors'] ?? null) {
                try {
                    if (is_callable($row['query'])) {
                        $row['query']();
                    } else {
                        $this->db->queryRaw($row['query']);
                    }
                } catch (FatalError) {
                }
            } else {
                if (is_callable($row['query'])) {
                    $row['query']();
                } else {
                    $this->db->queryRaw($row['query']);
                }
            }
        }
        if ($this->db instanceof Sqlite) {
            // optimize table and integrity after builder updates
            $this->db->execRaw('PRAGMA integrity_check');
            $this->db->execRaw('VACUUM');
        }
    }

    /**
     * Get queries that are unsafe to execute, queries that will modifiy/delete existing columns
     * Unsafe queries are only returned when no safe queries exist, as it is possible that safe queries obsoletes unsafe ones
     * @return array
     */
    public function getUnsafeQueries(): array
    {
        if ($this->getSafeQueries()) {
            return [];
        }
        // re-index
        return array_values($this->getQueries());
    }

    /**
     * Get only queries that are safe to execute
     * Queries that modify/delete any existing data will not be returned
     * @return array
     */
    public function getSafeQueries(): array
    {
        $queries = $this->getQueries();
        foreach ($queries as $key => $row) {
            if (!str_starts_with($row['type'], "create")) {
                unset($queries[$key]);
            }
        }
        // re-index
        return array_values($queries);
    }

    /**
     * Get all queries that are required to update the database
     * @return array
     */
    public function getQueries(): array
    {
        $requiredStorableSchemas = [];

        // framelix internal tables
        $storableSchema = new StorableSchema(StorableSchema::ID_TABLE);
        $storableSchema->addIndex('storableId', 'index');

        $storableSchemaProperty = $storableSchema->createProperty('id');
        $storableSchemaProperty->databaseType = 'bigint';
        $storableSchemaProperty->length = 18;
        $storableSchemaProperty->unsigned = true;
        $storableSchemaProperty->autoIncrement = true;

        $storableSchemaProperty = $storableSchema->createProperty('storableId');
        $storableSchemaProperty->databaseType = 'int';
        $storableSchemaProperty->length = 5;
        $storableSchemaProperty->unsigned = true;

        $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;

        $storableSchema = new StorableSchema(StorableSchema::SCHEMA_TABLE);
        $storableSchema->addIndex('storableClass', 'unique');

        $storableSchemaProperty = $storableSchema->createProperty('id');
        $storableSchemaProperty->databaseType = 'bigint';
        $storableSchemaProperty->length = 18;
        $storableSchemaProperty->unsigned = true;
        $storableSchemaProperty->autoIncrement = true;

        $storableSchemaProperty = $storableSchema->createProperty('storableClass');
        $storableSchemaProperty->databaseType = 'varchar';
        $storableSchemaProperty->length = 191;

        $storableSchemaProperty = $storableSchema->createProperty('storableClassParents');
        $storableSchemaProperty->databaseType = 'text';

        $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;

        // fetch all storable schemas
        foreach (Framelix::$registeredModules as $module) {
            $moduleFolder = FileUtils::getModuleRootPath($module);
            $storableFiles = FileUtils::getFiles("$moduleFolder/src/Storable", "~\.php$~", true);
            foreach ($storableFiles as $storableFile) {
                $storableClass = ClassUtils::getClassNameForFile($storableFile);
                $storableSchema = Storable::getStorableSchema($storableClass);
                // abstract classes are not need to be created
                if ($storableSchema->abstract) {
                    continue;
                }
                $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;
            }
        }

        $queries = [];
        $existingTables = $this->db->getTables(true);
        /** @var StorableSchema[] $existingStorableSchemas */
        $existingStorableSchemas = [];

        // get all existing properties and indexes
        foreach ($existingTables as $existingTable) {
            // for a table that have no schema representation now, we can skip to do further compare checks
            if (!isset($requiredStorableSchemas[$existingTable])) {
                continue;
            }
            $currentScheme = $requiredStorableSchemas[$existingTable];
            $existingScheme = new StorableSchema($currentScheme->className);
            $existingStorableSchemas[$existingTable] = $existingScheme;
            $rows = $this->db->getTableColumns($existingTable, true);
            foreach ($rows as $columnName => $row) {
                $storableSchemaProperty = $existingScheme->createProperty($columnName);
                if ($this->db instanceof Sqlite) {
                    if (str_contains($row, " AUTOINCREMENT")) {
                        $row = str_replace(" AUTOINCREMENT", "", $row);
                        $storableSchemaProperty->autoIncrement = true;
                    }
                    if (str_contains($row, " UNSIGNED")) {
                        $row = str_replace(" UNSIGNED", "", $row);
                        $storableSchemaProperty->unsigned = true;
                    }
                    if (str_contains($row, " PRIMARY KEY")) {
                        $row = str_replace(" PRIMARY KEY", "", $row);
                        $storableSchemaProperty->unsigned = true;
                    }
                    if (str_contains($row, " NULL")) {
                        $row = str_replace(" NULL", "", $row);
                        $storableSchemaProperty->allowNull = true;
                    }
                    $databaseType = trim($row);
                } else {
                    $databaseType = $row["Type"];
                    $unsignedPos = strpos(strtolower($databaseType), "unsigned");
                    if ($unsignedPos !== false) {
                        $databaseType = substr($databaseType, 0, $unsignedPos);
                    }
                    if ($row["Null"] === "YES") {
                        $storableSchemaProperty->allowNull = true;
                    }
                    $storableSchemaProperty->unsigned = $unsignedPos !== false;
                    $storableSchemaProperty->autoIncrement = str_contains($row["Extra"], "auto_increment");
                    $storableSchemaProperty->dbComment = $row['Comment'] ?: null;
                }
                $typeExp = explode("(", trim($databaseType, "()"));
                $length = isset($typeExp[1]) ? explode(",", $typeExp[1]) : [];
                $databaseType = trim($typeExp[0]);
                $storableSchemaProperty->databaseType = $databaseType;
                if (isset($length[0])) {
                    $storableSchemaProperty->length = (int)$length[0];
                }
                if (isset($length[1]) && $length[1] > 0) {
                    $storableSchemaProperty->decimals = (int)$length[1];
                }
            }
            $rows = $this->db->getTableIndexes($existingTable, true);
            foreach ($rows as $dbIndexName => $row) {
                // just store that we have an index with this name, doesn't matter which because we skip if index already exist
                if ($this->db instanceof Sqlite) {
                    $type = "index";
                    if (str_contains($row, " UNIQUE")) {
                        $row = str_replace(" UNIQUE", "", $row);
                        $type = "unique";
                    }
                    $row = substr($row, strpos($row, "(") + 1, -1);
                    $columns = explode(",", $row);
                    $properties = [];
                    foreach ($columns as $column) {
                        $properties[] = trim($column, " `\"");
                    }
                    $existingScheme->addIndex($properties, $type);
                } else {
                    $existingScheme->addIndex(
                        explode(",", str_replace(" ", "", $row['Column_name'])),
                        $row['Key_name'] === 'PRIMARY' ? 'primary' : 'index'
                    );
                }
            }
        }

        // at first create all required tables with the id column
        foreach ($requiredStorableSchemas as $tableName => $storableSchema) {
            if (isset($existingTables[$tableName])) {
                continue;
            }
            if ($this->db instanceof Sqlite) {
                $query = "CREATE TABLE " . $this->db->quoteIdentifier($storableSchema->tableName) .
                    " (" . $this->db->quoteIdentifier("id") . " INTEGER PRIMARY KEY ";
                if ($storableSchema->properties['id']->autoIncrement) {
                    $query .= " AUTOINCREMENT";
                }
                $query .= ")";
            } else {
                $query = "CREATE TABLE " . $this->db->quoteIdentifier($storableSchema->tableName) .
                    " (" . $this->db->quoteIdentifier("id") . " BIGINT UNSIGNED NOT NULL";
                if ($storableSchema->properties['id']->autoIncrement) {
                    $query .= " AUTO_INCREMENT";
                }
                $query .= ", PRIMARY KEY (" . $this->db->quoteIdentifier("id") .
                    ") USING BTREE) COLLATE='" . self::MYSQL_DEFAULT_COLLATION .
                    "' ENGINE=" . self::MYSQL_DEFAULT_ENGINE;
            }
            $queries[] = [
                "type" => 'create-table',
                "query" => $query
            ];
        }

        // creating columns and indexes
        foreach ($requiredStorableSchemas as $tableName => $storableSchema) {
            $existingStorableSchema = $existingStorableSchemas[$tableName] ?? null;
            foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
                // id field is already created by previous create table action
                if ($propertyName === 'id') {
                    continue;
                }
                $queryPartExistingTest = null;
                $existingProperty = $existingStorableSchema->properties[$propertyName] ?? null;
                if ($existingProperty) {
                    $queryPartExistingTest = self::getQueryForAlterTableColumn($existingProperty, "");
                }
                $queryPartNewTest = self::getQueryForAlterTableColumn($storableSchemaProperty, "");
                $columnRequireChanges = $queryPartExistingTest !== $queryPartNewTest;

                if (!$columnRequireChanges) {
                    continue;
                }
                $tableNameQuoted = $this->db->quoteIdentifier($tableName);

                // sqlite do not support CHANGE COLUMN, so instead this requires a workaround of
                // 1. rename old column to a tmp_name
                // 2. create new
                // 3. copy old column data to new column
                // 4. remove old column
                // additionaly drop and recreate indexes of affected columns
                if ($existingProperty && $this->db instanceof Sqlite) {
                    $propertyNameQuoted = $this->db->quoteIdentifier($propertyName);
                    $propertyNameTmpQuoted = $this->db->quoteIdentifier($propertyName . "_tmp_" . rand(0, 999));
                    $queryPartNew = self::getQueryForAlterTableColumn($storableSchemaProperty, "ADD");
                    $dropIndexes = [];
                    $createIndexes = [];
                    foreach ($existingStorableSchema->indexes as $indexKey => $indexOptions) {
                        if (!in_array($propertyName, $indexOptions['properties'])) {
                            continue;
                        }
                        $dropIndexes[] = $this->getQueryForDropTableIndex($existingStorableSchema, $indexKey);
                        $createIndexes[] = $this->getQueryForCreateTableIndex($existingStorableSchema, $indexKey);
                    }
                    $query = "
                        BEGIN;
                        " . implode(";", $dropIndexes) . ";
                        ALTER TABLE $tableNameQuoted RENAME COLUMN $propertyNameQuoted TO $propertyNameTmpQuoted;
                        ALTER TABLE $tableNameQuoted $queryPartNew;
                        UPDATE $tableNameQuoted SET $propertyNameQuoted = $propertyNameTmpQuoted;
                        ALTER TABLE $tableNameQuoted DROP COLUMN $propertyNameTmpQuoted;
                        " . implode(";", $createIndexes) . ";
                        COMMIT;
                    ";
                    $queries[] = [
                        "type" => 'alter-column',
                        "testa" => $queryPartExistingTest,
                        "testb" => $queryPartNewTest,
                        "query" => function () use ($query) {
                            if ($this->db instanceof Sqlite) {
                                $this->db->execRaw($query);
                            }
                        }
                    ];
                    continue;
                }

                if ($queryPartExistingTest !== $queryPartNewTest) {
                    $queryPartNew = self::getQueryForAlterTableColumn(
                        $storableSchemaProperty,
                        $existingProperty ? 'CHANGE COLUMN' : 'ADD'
                    );
                    $query = "ALTER TABLE $tableNameQuoted $queryPartNew";
                    $queries[] = [
                        "type" => $existingProperty ? 'alter-column' : 'create-column',
                        "query" => $query
                    ];
                }
            }
            if ($storableSchema->indexes) {
                foreach ($storableSchema->indexes as $indexName => $indexOptions) {
                    // if the same index exist, skip, we not allow modifying it
                    if (isset($existingStorableSchema->indexes[$indexName])) {
                        continue;
                    }
                    $queries[] = [
                        "type" => 'create-index',
                        "query" => self::getQueryForCreateTableIndex($storableSchema, $indexName)
                    ];
                }
            }
            // adding required meta storable class
            if ($storableSchema->tableName !== StorableSchema::ID_TABLE && $storableSchema->tableName !== StorableSchema::SCHEMA_TABLE) {
                $storableClassParents = $this->db->escapeValue(
                    JsonUtils::encode(array_reverse(array_keys($storableSchema->parentStorableSchemas)))
                );
                $checkQuery = '
                    SELECT * 
                    FROM ' . StorableSchema::SCHEMA_TABLE . ' 
                    WHERE storableClass = ' . $this->db->escapeValue($storableSchema->className) . '
                ';
                $existingSchemeData = null;
                if (isset($existingTables[StorableSchema::SCHEMA_TABLE])) {
                    $existingSchemeData = $this->db->fetchAssocOne($checkQuery);
                }
                if (
                    $this->db->escapeValue($existingSchemeData['storableClassParents'] ?? null) !== $storableClassParents
                ) {
                    if ($existingSchemeData['id'] ?? null) {
                        $query = '
                            UPDATE ' . StorableSchema::SCHEMA_TABLE . ' 
                            SET storableClassParents =' . $this->db->escapeValue($storableClassParents) . '
                            WHERE id = ' . (int)$existingSchemeData['id'] . '
                        ';
                    } else {
                        $query = '
                            INSERT INTO ' . StorableSchema::SCHEMA_TABLE . ' (storableClass, storableClassParents) 
                            VALUES (' . $this->db->escapeValue($storableSchema->className) . ', ' . $storableClassParents . ')';
                    }
                    $queries[] = [
                        "type" => 'create-schema',
                        "query" => $query
                    ];
                }
            }
        }
        foreach ($existingTables as $existingTable) {
            // only obsolete framelix tables can be deleted
            if (!str_starts_with($existingTable, "framelix_")) {
                continue;
            }
            if (!isset($requiredStorableSchemas[$existingTable])) {
                $queries[] = [
                    "type" => 'drop-table',
                    "query" => "DROP TABLE " . $this->db->quoteIdentifier($existingTable)
                ];
            }
        }
        foreach ($existingStorableSchemas as $tableName => $storableSchema) {
            // only obsolete framelix tables can be deleted
            if (!str_starts_with($tableName, "framelix_")) {
                continue;
            }
            // whole table will be dropped already
            if (!isset($requiredStorableSchemas[$tableName])) {
                continue;
            }
            // index drop first (is required in sqlite)
            foreach ($storableSchema->indexes as $indexKey => $indexOptions) {
                // don't touch primary indexes
                if ($indexOptions['type'] === 'primary') {
                    continue;
                }
                if (!isset($requiredStorableSchemas[$tableName]->indexes[$indexKey])) {
                    $queries[] = [
                        "type" => 'drop-index',
                        "query" => $this->getQueryForDropTableIndex($storableSchema, $indexKey),
                        "ignoreErrors" => true
                    ];
                }
            }
            // drop properties last
            foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
                if ($propertyName === 'id') {
                    continue;
                }
                if (!isset($requiredStorableSchemas[$tableName]->properties[$propertyName])) {
                    $queries[] = [
                        "type" => 'drop-column',
                        "query" =>
                            "ALTER TABLE " . $this->db->quoteIdentifier($tableName) .
                            " DROP COLUMN " . $this->db->quoteIdentifier($propertyName),
                        "ignoreErrors" => true,
                    ];
                }
            }
        }
        return $queries;
    }

    /**
     * Get query part for alter table column
     * @param StorableSchemaProperty $storableSchemaProperty
     * @param string $columnModifyCommand CHANGE COLUMN, ADD [COLUMN}
     * @return string
     */
    private function getQueryForAlterTableColumn(
        StorableSchemaProperty $storableSchemaProperty,
        string $columnModifyCommand
    ): string {
        $queryColumnParts = [];
        $queryColumnParts[] = $columnModifyCommand;
        $queryColumnParts[] = $this->db->quoteIdentifier($storableSchemaProperty->name);
        if ($columnModifyCommand === "CHANGE COLUMN") {
            $queryColumnParts[] = $this->db->quoteIdentifier($storableSchemaProperty->name);
        }
        $databaseType = strtoupper($storableSchemaProperty->databaseType);
        $queryColumnParts[] = strtoupper($storableSchemaProperty->databaseType);

        $lengthAllowed =
            $databaseType === "FLOAT"
            || $databaseType === "DOUBLE"
            || $databaseType === "DECIMAL"
            || $databaseType === "REAL"
            || $databaseType === "TINYINT"
            || str_ends_with($databaseType, "CHAR")
            || str_ends_with($databaseType, "BINARY");
        if ($lengthAllowed && is_int($storableSchemaProperty->length)) {
            if (is_int($storableSchemaProperty->decimals)) {
                $queryColumnParts[] = "($storableSchemaProperty->length, $storableSchemaProperty->decimals)";
            } else {
                $queryColumnParts[] = "($storableSchemaProperty->length)";
            }
        }
        if ($storableSchemaProperty->unsigned) {
            $queryColumnParts[] = "UNSIGNED";
        }
        $queryColumnParts[] = ($storableSchemaProperty->allowNull ? 'NULL' : 'NOT NULL');
        // here would be auto_increment, but we do not need to support altering tables with auto_increment
        // there is only one table with AI which is always created by default
        if ($storableSchemaProperty->dbComment && !($this->db instanceof Sqlite)) {
            $queryColumnParts[] = "COMMENT " . $this->db->escapeValue($storableSchemaProperty->dbComment);
        }
        if ($storableSchemaProperty->after && !($this->db instanceof Sqlite)) {
            $queryColumnParts[] = "AFTER " . $this->db->quoteIdentifier($storableSchemaProperty->after->name);
        }
        return implode(" ", $queryColumnParts);
    }

    /**
     * Get the query to drop a index from the database
     * @param StorableSchema $scheme
     * @param string $indexKey
     * @return string
     */
    public function getQueryForDropTableIndex(StorableSchema $scheme, string $indexKey): string
    {
        $indexOptions = $scheme->indexes[$indexKey];
        $tableName = $scheme->tableName;
        $indexName = $this->getIndexNameForProperties($tableName, $indexOptions['properties']);
        if ($this->db instanceof Sqlite) {
            $query = ["DROP INDEX " . $this->db->quoteIdentifier($indexName)];
        } else {
            $query = ["ALTER TABLE " . $this->db->quoteIdentifier($tableName)];
            $query[] = " DROP INDEX " . $this->db->quoteIdentifier($indexName);
        }
        return implode(" ", $query);
    }

    /**
     * Get the query to create a index in the database
     * @param StorableSchema $scheme
     * @param string $indexKey
     * @return string
     */
    public function getQueryForCreateTableIndex(StorableSchema $scheme, string $indexKey): string
    {
        $indexOptions = $scheme->indexes[$indexKey];
        $tableName = $scheme->tableName;
        $indexName = $this->getIndexNameForProperties($tableName, $indexOptions['properties']);
        $columnNames = [];
        foreach ($indexOptions['properties'] as $columnName) {
            $columnNames[] = $this->db->quoteIdentifier($columnName);
        }
        if ($this->db instanceof Sqlite) {
            $query = ["CREATE"];
            if ($indexOptions['type'] === 'unique') {
                $query[] = "UNIQUE INDEX";
            } else {
                $query[] = "INDEX";
            }
            $query[] = $this->db->quoteIdentifier($indexName) . " ON " . $this->db->quoteIdentifier($tableName);
            $query[] = "(" . implode(", ", $columnNames) . ")";
        } else {
            $query = ["ALTER TABLE " . $this->db->quoteIdentifier($tableName) . " ADD"];
            if ($indexOptions['type'] === 'index') {
                $query[] = "INDEX";
            } elseif ($indexOptions['type'] === 'unique') {
                $query[] = "UNIQUE INDEX";
            } elseif ($indexOptions['type'] === 'fulltext') {
                $query[] = "FULLTEXT INDEX";
            }
            if ($indexOptions['type'] !== 'primary') {
                $query[] = $this->db->quoteIdentifier($indexName);
            }
            $query[] = "(" . implode(", ", $columnNames) . ")";
        }
        return implode(" ", $query);
    }

    /**
     * Index names in db are hashed to avoid length issues
     * @param string $tableName
     * @param string|string[] $properties
     * @return string
     */
    public function getIndexNameForProperties(string $tableName, string|array $properties): string
    {
        return "framelix_" . md5($tableName . "_" . implode(",", is_array($properties) ? $properties : [$properties]));
    }
}