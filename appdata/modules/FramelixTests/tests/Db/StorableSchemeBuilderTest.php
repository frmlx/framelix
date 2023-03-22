<?php

namespace Db;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\Storable;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\StorableException\TestStorableNoType;
use Framelix\FramelixTests\StorableException\TestStorableUnsupportedType;
use Framelix\FramelixTests\TestCase;

use function count;

final class StorableSchemeBuilderTest extends TestCase
{
    private MysqlStorableSchemeBuilder $builder;

    public function testBuilderQueries(): void
    {
        $this->setupDatabase();
        $this->cleanupDatabase();
        $db = Mysql::get('test');
        $schema = Storable::getStorableSchema(TestStorable2::class);
        // assert exact same schema (cached already)
        $this->assertSame($schema, Storable::getStorableSchema(TestStorable2::class));
        $this->builder = new MysqlStorableSchemeBuilder($db);
        // first create all things
        $queries = $this->builder->getQueries();
        // all new queries that do not modify anything are considered safe
        $this->assertCount(count($queries), $this->builder->getSafeQueries());
        $this->builder->executeQueries($queries);
        // next check should result in 0 queries
        $queries = $this->builder->getQueries();
        $this->assertCount(0, $queries);
        // calling the builder immediately after should not need to change anything
        $queries = $this->builder->getQueries();
        $this->assertQueryCount(0, $queries, true);
        // deleting a column and than the builder should recreate this including the index
        // 3 queries because 1x adding, 1x reordering columns and 1x creating an index
        $db->query("ALTER TABLE framelix_framelixtests_storable_teststorable2 DROP COLUMN `createUser`");
        $queries = $this->builder->getQueries();
        // 1 of 3 is unsafe, so we have 2 safe queries
        $this->assertCount(2, $this->builder->getSafeQueries());
        // when having safe queries, there couldn't be any unsafe queries
        // as safe queries always need to be executed prior to generate unsafe queries correctly
        $this->assertCount(0, $this->builder->getUnsafeQueries());
        $this->assertQueryCount(3, $queries, true);
        // modifying some table data to simulate changed property behaviour
        $db->query(
            'ALTER TABLE `framelix_framelixtests_storable_teststorable2`
	CHANGE COLUMN `createTime` `createTime` DATE NULL DEFAULT NULL AFTER `id`,
	CHANGE COLUMN `longText` `longText` VARCHAR(50) NULL DEFAULT NULL COLLATE \'utf8mb4_unicode_ci\' AFTER `name`,
	CHANGE COLUMN `selfReferenceOptional` `selfReferenceOptionals` BIGINT(18) UNSIGNED NULL DEFAULT NULL,
	DROP INDEX `selfReferenceOptional`'
        );

        $queries = $this->builder->getQueries();
        $this->assertQueryCount(6, $queries, true);

        // calling the builder immediately after should not need to change anything
        $queries = $this->builder->getQueries();
        $this->assertQueryCount(0, $queries, true);
        // droping an index and let the system recreate it
        $db->query("ALTER TABLE `framelix_framelix_storable_user` DROP INDEX `updateUser`");
        $queries = $this->builder->getQueries();
        $this->assertQueryCount(1, $queries, true);
        // adding some additional obsolete columns and tables that the builder should delete
        $db->query(
            "ALTER TABLE `framelix_framelix_storable_user`
	ADD COLUMN `unusedTime` DATETIME NULL DEFAULT NULL,
	ADD INDEX `flagLocked` (`flagLocked`)"
        );
        $db->query('CREATE TABLE `framelix_unused_table` (`id` INT(11) NULL DEFAULT NULL)');
        // 3rd party tables are untouched by default
        $db->query('CREATE TABLE `unused_table` (`id` INT(11) NULL DEFAULT NULL)');
        // altering/deleting a existing column/table or is always unsafe
        $queries = $this->builder->getSafeQueries();
        $this->assertCount(0, $queries);
        $queries = $this->builder->getUnsafeQueries();
        $this->assertCount(3, $queries);
        $queries = $this->builder->getQueries();
        $this->assertQueryCount(3, $queries, true);
    }

    public function testUnsupportedDbPropertyType(): void
    {
        $this->assertExceptionOnCall(function () {
            Storable::getStorableSchema(TestStorableUnsupportedType::class);
        });

        $this->assertExceptionOnCall(function () {
            $type = Storable::getStorableSchema(TestStorableUnsupportedType::class);
            $type->properties['floatNumber']->databaseType = 'foobar';
            Storable::getStorableSchema(TestStorableUnsupportedType::class);
        });
    }

    public function testNoDbPropertyType(): void
    {
        $this->assertExceptionOnCall(function () {
            Storable::getStorableSchema(TestStorableNoType::class);
        });
    }

    /**
     * Assert special query count which ignores some irrelevant queries
     * @param int $count
     * @param array $queries
     * @param bool $execute Execute queries after assert
     */
    private function assertQueryCount(int $count, array $queries, bool $execute): void
    {
        foreach ($queries as $key => $row) {
            // insert metas are ignored, as they are always here
            if ($row['type'] === 'insert-meta') {
                unset($queries[$key]);
            }
        }
        $this->assertCount($count, $queries);
        if ($execute) {
            $this->builder->executeQueries($queries);
        }
    }
}