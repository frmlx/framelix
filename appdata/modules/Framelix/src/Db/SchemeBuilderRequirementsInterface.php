<?php

namespace Framelix\Framelix\Db;

/**
 * An interface that a SQL connection must implement to be eligible for scheme builder features
 */
interface SchemeBuilderRequirementsInterface
{
    /**
     * Dump sql table statements (Create table and insert data) for given table name to given file
     * @param string $path
     * @param string $tableName
     */
    public function dumpSqlTableToFile(string $path, string $tableName): void;

    /**
     * Get all existing database tables in lower case
     * @param bool $flushCache If false the result is cached by default if already called previously
     * @return string[]
     */
    public function getTables(bool $flushCache = false): array;

    /**
     * Get all existing table columns with all possible meta information
     * Key of array is column name, value is array of metadata to that column
     * @param bool $flushCache If false the result is cached by default if already called previously
     * @return array Data depends on db type
     */
    public function getTableColumns(string $table, bool $flushCache = false): array;

    /**
     * Get all existing table indexes with all possible meta information
     * Key of array is index name, value is array of metadata to that index
     * @param bool $flushCache If false the result is cached by default if already called previously
     * @return array Data depends on db type
     */
    public function getTableIndexes(string $table, bool $flushCache = false): array;
}