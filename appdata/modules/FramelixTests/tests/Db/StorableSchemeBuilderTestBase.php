<?php

namespace Db;

use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\User;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\StorableException\TestStorableNoType;
use Framelix\FramelixTests\StorableException\TestStorableUnsupportedType;
use Framelix\FramelixTests\TestCaseDbTypes;

use PHPUnit\Framework\Attributes\Depends;

use function count;
use function in_array;
use function print_r;

abstract class StorableSchemeBuilderTestBase extends TestCaseDbTypes
{
    private SqlStorableSchemeBuilder $builder;

    public function testBuilderQueries(): void
    {
        $this->setupDatabase();
        $db = Sql::get('test');
        $schema = Storable::getStorableSchema(TestStorable2::class);
        // assert exact same schema (cached already)
        $this->assertSame($schema, Storable::getStorableSchema(TestStorable2::class));

        $this->builder = new SqlStorableSchemeBuilder($db);
        // first create all things
        $queries = $this->builder->getQueries();
        // all new queries that do not modify anything are considered safe
        $this->assertCount(count($queries), $this->builder->getSafeQueries());
        $this->builder->executeQueries($queries);


        // next check should result in 0 queries
        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(0, $queries, false);
        // calling the builder immediately after should not need to change anything
        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(0, $queries, true);
    }

    #[Depends("testBuilderQueries")]
    public function testBuilderQueries2(): void
    {
        $db = Sql::get('test');
        $this->builder = new SqlStorableSchemeBuilder($db);
        $queries = $this->builder->getQueries();
        // still no queries to execute, even in another process
        $this->assertBuilderQueryCount(0, $queries, true);

        // modifying some table data to simulate changes property behaviour
        $scheme = Storable::getStorableSchema(TestStorable2::class);
        $scheme->properties['intNumber']->databaseType = 'DATE';
        $scheme->properties['longText']->databaseType = 'VARCHAR';
        $scheme->properties['longText']->length = 50;
        $scheme->properties['selfReferenceOptional']->databaseType = 'TEXT';
        $scheme->properties['selfReferenceOptional']->unsigned = false;
        unset($scheme->indexes['selfReferenceOptional']);

        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(4, $queries, true);
    }

    #[Depends("testBuilderQueries2")]
    public function testBuilderQueries3(): void
    {
        // previously we have changed 3 columns and dropped one index
        $db = Sql::get('test');
        $this->builder = new SqlStorableSchemeBuilder($db);

        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(4, $queries, true);

        // calling the builder immediately after should not need to change anything
        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(0, $queries, true);

        // droping indexes, modifying some properties, adding empty table and let the system recreate and fix it
        $scheme = Storable::getStorableSchema(User::class);
        $scheme->indexes = [];
        $scheme->addIndex('flagLocked', 'index');
        $fakeProperty = $scheme->createProperty('unusedTime');
        $fakeProperty->databaseType = 'DATETIME';
        $this->builder->executeQueries($this->builder->getQueries());
        $db->query(
            'CREATE TABLE ' . $db->quoteIdentifier('framelix_unused_table') . ' (`id` INTEGER NULL DEFAULT NULL)'
        );
        // 3rd party tables are untouched by default
        $db->query('CREATE TABLE ' . $db->quoteIdentifier('unused_table') . ' (`id` INTEGER NULL DEFAULT NULL)');
    }

    #[Depends("testBuilderQueries3")]
    public function testBuilderQueries4(): void
    {
        $db = Sql::get('test');
        $this->builder = new SqlStorableSchemeBuilder($db);

        // previously we have dropped all indexes, and now let them recreate it is considered safe
        $queries = $this->builder->getSafeQueries();
        $this->assertBuilderQueryCount(3, $queries, true, ['create-index']);

        $queries = $this->builder->getUnsafeQueries();
        // previously we have created a fake table, a fake column and a fake index
        // everything to change that is considered unsafe as it will modify existing data
        $this->assertBuilderQueryCount(1, $queries, true, ['drop-table']);
        $this->assertBuilderQueryCount(1, $queries, true, ['drop-column']);
        $this->assertBuilderQueryCount(1, $queries, true, ['drop-index']);

        // in total, no other queries should be left
        $queries = $this->builder->getQueries();
        $this->assertBuilderQueryCount(0, $queries, false);
    }


    #[Depends("testBuilderQueries3")]
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


    #[Depends("testUnsupportedDbPropertyType")]
    public function testNoDbPropertyType(): void
    {
        $this->assertExceptionOnCall(function () {
            Storable::getStorableSchema(TestStorableNoType::class);
        });
    }

    /**
     * Assert query count that comes from the builder
     * @param int $count
     * @param array $queries
     * @param bool $execute Execute queries after assert
     * @param string[]|null $allowedQueryTypes Only count that allowed query types
     */
    protected function assertBuilderQueryCount(
        int $count,
        array $queries,
        bool $execute,
        ?array $allowedQueryTypes = null
    ): void {
        if ($allowedQueryTypes) {
            foreach ($queries as $key => $row) {
                if (!in_array($row['type'], $allowedQueryTypes)) {
                    unset($queries[$key]);
                }
            }
        }
        $this->assertCount($count, $queries, print_r($queries, true));
        if ($execute) {
            $this->builder->executeQueries($queries);
        }
    }
}