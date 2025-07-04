<?php

namespace Db;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Exception\Redirect;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Time;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\Storable\Deeper\TestStorableDeeper;
use Framelix\FramelixTests\Storable\TestStorable1;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\Storable\TestStorablePrefetch;
use Framelix\FramelixTests\TestCaseDbTypes;

use PHPUnit\Framework\Attributes\Depends;

use function array_chunk;
use function array_keys;
use function array_reverse;
use function array_values;
use function count;
use function implode;
use function in_array;
use function shuffle;
use function str_repeat;
use function var_export;

abstract class StorableTestBase extends TestCaseDbTypes
{
    /**
     * Executed queries
     * @var int
     */
    private int $executedQueries = 0;

    /**
     * Some dummy values for tests
     * @var array
     */
    private array $dummyValues = [];

    public function testStoreAndDelete(): void
    {
        // enable system logs
        Config::$enabledBuiltInSystemEventLogs = [
            SystemEventLog::CATEGORY_STORABLE_CREATED => true,
            SystemEventLog::CATEGORY_STORABLE_UPDATED => true,
            SystemEventLog::CATEGORY_STORABLE_DELETED => true
        ];
        $this->setupDatabase();
        $db = Sql::get('test');

        $this->startRecordExecutedQueries();
        // we have no objects in DB, so this does create a new entry
        $storable = TestStorable1::getByIdOrNew(1);
        $this->assertNull($storable->id);
        $storable->name = "foobar@dev.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = DateTime::create('now');
        $storable->date = Date::create('now');
        $storable->store();
        $storableReference = $storable;
        // 1x select because of getByIdOrNew
        // 1x to insert into id table
        // 1x to insert into storable table
        // 1x to fetch storableClassId
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(10);

        $this->startRecordExecutedQueries();
        $storable = $storable->clone();
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = DateTime::create('now');
        $storable->date = Date::create('now');
        $storable->selfReferenceOptional = $storableReference;
        $storable->store();
        $storable1 = $storable;
        // 1x to insert into id table
        // 1x to insert into storable table
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(8);
        $this->assertSame(
            'foobar@test2.me',
            $db->fetchOne("SELECT name FROM framelix_framelixtests_storable_teststorable1 WHERE id = " . $storable)
        );

        $this->startRecordExecutedQueries();
        $storable = new TestStorable2();
        // modified timestamp is null for new objects
        $this->assertNull($storable->getModifiedTimestampTableCell());
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->longTextLazy = str_repeat("foo", 1000);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime("2000-01-01 12:23:44");
        $storable->date = Date::create("2000-01-01");
        $storable->otherReferenceOptional = $storableReference;
        $storable->time = Time::create("12:00:01");
        $storable->updateTime = DateTime::create('now - 10 seconds');
        $storable->store();


        $storable2 = $storable;
        $storableReference = $storable;
        // 1x to insert into id table
        // 1x to insert into storable table
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(8);
        $this->assertSame(
            'foobar@test2.me',
            $db->fetchOne("SELECT name FROM framelix_framelixtests_storable_teststorable2 WHERE id = " . $storable)
        );

        $this->startRecordExecutedQueries();
        $storable = new TestStorable2();
        $storable->name = "foobar@test3.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime();
        $storable->date = Date::create('now');
        $storable->selfReferenceOptional = $storableReference;
        $storable->store();
        // 1x to insert into id table
        // 1x to insert into storable table
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(8);
        $this->assertSame(
            'foobar@test3.me',
            $db->fetchOne("SELECT name FROM framelix_framelixtests_storable_teststorable2 WHERE id = " . $storable)
        );

        $this->startRecordExecutedQueries();
        $storable->name = "foobar@test4.me";
        $storable->store();
        // 1x to update
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(7);
        $this->assertSame(
            'foobar@test4.me',
            $db->fetchOne("SELECT name FROM framelix_framelixtests_storable_teststorable2 WHERE id = " . $storable)
        );

        $this->startRecordExecutedQueries();
        $storable->store();
        // nothing changed no query to execute
        $this->assertExecutedQueries(0);

        $this->startRecordExecutedQueries();
        $storableId = $storable->id;
        $storable->delete();
        $this->assertNull($storable->id);
        // delete from id table and actual storable table
        // 1x to delete from id table
        // 1x to delete from storable table
        // 2x systemlog insert
        // 4x for begin and commit transaction
        $this->assertExecutedQueries(8);
        $this->assertSame(
            null,
            $db->fetchOne(
                "SELECT name FROM framelix_framelixtests_storable_teststorable2 WHERE id = " . $storableId
            )
        );

        $storable->store();

        $arr = [];
        // create more storables for next tests
        for ($i = 0; $i <= 50; $i++) {
            $storableNew = $storable2->clone();
            $storableNew->otherReferenceOptional = $storable1;
            $storableNew->selfReferenceOptional = $storable2;
            $storableNew->store();
            $arr[$storableNew->id] = $storableNew;

            $storableNewPrefetch = new TestStorablePrefetch();
            $storableNewPrefetch->otherReference = $storableNew;
            $storableNewPrefetch->otherReferenceNoPrefetch = $storableNew;
            $storableNewPrefetch->otherReferenceReducedPrefetch = $storableNew;
            // debugging with a fixed array is easier
            if (count($arr) >= 33) {
                $chunk = array_chunk($arr, 33, true)[0];
                shuffle($chunk);
                $chunkRev = array_chunk(array_reverse($arr, true), 33, true)[0];
                shuffle($chunkRev);
                // raw reference ids to later compare against
                $storableNewPrefetch->requiredIds = [
                    'normal' => array_values($chunk),
                    'reverse' => array_values($chunkRev),
                ];
            }
            $storableNewPrefetch->store();
        }
    }

    #[Depends("testStoreAndDelete")]
    public function testFetch(): void
    {
        // fetch last 50 teststorables1 as they all have the same data applied
        // which makes checking for getter values easy here
        $this->startRecordExecutedQueries();
        $storables = TestStorable2::getByCondition(sort: "-id", limit: 50);
        $this->assertExecutedQueries(1);
        $this->assertCount(50, $storables);
        $this->startRecordExecutedQueries();
        foreach ($storables as $storable) {
            $this->assertSame("foobar@test2.me", $storable->name);
            $this->assertSame(str_repeat("foo", 100), $storable->longText);
            $this->assertSame(69, $storable->intNumber);
            $this->assertSame(6.9, $storable->floatNumber);
            $this->assertSame(true, $storable->boolFlag);
            $this->assertSame(['foobar', 1], $storable->jsonData);
            $this->assertSame("2000-01-01 12:23:44", $storable->dateTime->format("Y-m-d H:i:s"));
            $this->assertSame("2000-01-01", $storable->dateTime->format("Y-m-d"));
            $this->assertEquals(Time::create("12:00:01"), $storable->time);
            $this->assertEquals(Date::create("2000-01-01"), $storable->date);
            $this->assertNull($storable->dateOptional);
            $this->assertNull($storable->longTextOptional);
            $this->assertNull($storable->boolFlagOptional);
            $this->assertNull($storable->dateTimeOptional);
            $this->assertNull($storable->floatNumberOptional);
            $this->assertNull($storable->intNumberOptional);
            $this->assertNull($storable->jsonDataOptional);
            if ($storable->selfReferenceOptional) {
                $this->assertInstanceOf(TestStorable2::class, $storable->selfReferenceOptional);
                $this->assertSame(
                    (int)$storable->getOriginalDbValueForProperty("selfReferenceOptional"),
                    $storable->selfReferenceOptional->id
                );
            }
            if ($storable->otherReferenceOptional) {
                $this->assertInstanceOf(TestStorable1::class, $storable->otherReferenceOptional);
                $this->assertSame(
                    (int)$storable->getOriginalDbValueForProperty("otherReferenceOptional"),
                    $storable->otherReferenceOptional->id
                );
            }
            $this->assertIsInt($storable->getNewDbValueForProperty('intNumber'));
            $this->assertNull($storable->getNewDbValueForProperty('notExist'));

            // some getter tests
            $this->assertNull($storable->getDetailsUrl());
        }
        // for each call on a referenced property of on type of class there is only one query needed
        // no matter how many storables are processed
        // we have TestStorable1 and TestStorable2 as references, so expect 2 additional queries
        $this->assertExecutedQueries(2);

        // fetch with corrupt ids
        $this->assertCount(0, TestStorable2::getByIds(['foo', 'ß1a<>']));
        $this->assertNull(TestStorable2::getById('1`'));

        // fetch with no ids
        $this->assertCount(0, TestStorable2::getByIds([]));

        // fetch with no result
        $this->assertNull(TestStorable2::getByConditionOne("id IS NULL"));

        // force delete, will be later tested if delete without force will throw exception
        $storable = new TestStorableDeeper();
        $storable->store();
        $this->assertIsInt($storable->getDbValue());
        $storable->delete(true);
    }

    #[Depends("testFetch")]
    public function testDepthFetch(): void
    {
        $storables = TestStorable2::getByCondition(
            "selfReferenceOptional.selfReferenceOptional IS NULL AND selfReferenceOptional.createTime IS NOT NULL"
        );
        $this->assertGreaterThan(0, count($storables));
    }

    #[Depends("testDepthFetch")]
    public function testUpdate(): void
    {
        // updateTime does update by default
        $storable = TestStorable2::getByConditionOne(sort: ['+id']);
        $storable->name = $storable->name . "Add";
        $upateTime = $storable->updateTime->getTimestamp();
        $storable->store();
        $this->assertNotEquals($upateTime, $storable->updateTime->getTimestamp());
        $this->assertInstanceOf(TableCell::class, $storable->getModifiedTimestampTableCell());
        // preserve updateTime
        $storablePrev = $storable;
        $storable = TestStorable2::getByConditionOne(sort: ['+id'], offset: 1);
        $storable->name = $storable->name . "Add";
        $storable->preserveUpdateUserAndTime();
        $upateTime = $storable->updateTime->getTimestamp();
        $storable->store();
        $this->assertNotEquals($storablePrev, $storable);
        $this->assertEquals($upateTime, $storable->updateTime->getTimestamp());
    }

    #[Depends("testDepthFetch")]
    public function testFetchChildsOfAbstractStorables(): void
    {
        // fetching all from type storable are effectively all that exist
        $storables = Storable::getByCondition(connectionId: "test");
        $this->assertCount((int)Sql::get('test')->fetchOne('SELECT COUNT(*) FROM framelix__id'), $storables);
        $extendedIds = [];
        foreach ($storables as $storable) {
            if ($storable instanceof StorableExtended) {
                $extendedIds[] = $storable->id;
            }
        }
        $storables = StorableExtended::getByCondition(connectionId: "test");
        $this->assertCount(count($extendedIds), $storables);

        $chunk = array_chunk($extendedIds, 3)[0];
        $storables = StorableExtended::getByIds($chunk, connectionId: "test");
        $this->assertSame($chunk, array_keys($storables));
    }

    #[Depends("testFetchChildsOfAbstractStorables")]
    public function testDatatypesSetter(): void
    {
        $this->dummyValues = [
            'bool' => true,
            'int' => 1,
            'float' => 1.0,
            'string' => "foo",
            'array' => ["blub"],
            'mixed' => ["blub"],
            DateTime::class => new DateTime(),
            TestStorable2::class => new TestStorable2(),
            TestStorable1::class => new TestStorable1()
        ];
        $storable = TestStorable2::getByConditionOne(condition: "-id", sort: "-id");
        $this->assertStorablePropertyValueSetter($storable, "id", ['int']);
        $this->assertStorablePropertyValueSetter($storable, "name", ['string']);
        $this->assertStorablePropertyValueSetter($storable, "intNumber", ['int']);
        $this->assertStorablePropertyValueSetter($storable, "floatNumber", ['float']);
        $this->assertStorablePropertyValueSetter($storable, "boolFlag", ['bool']);
        $this->assertStorablePropertyValueSetter($storable, "jsonData", array_keys($this->dummyValues));
        $this->assertStorablePropertyValueSetter($storable, "selfReferenceOptional", [TestStorable2::class]);
        $this->assertStorablePropertyValueSetter($storable, "dateTime", [DateTime::class]);
    }

    #[Depends("testDatatypesSetter")]
    public function testDatatypesGetter(): void
    {
        $storable = TestStorable2::getByConditionOne(
            "selfReferenceOptional IS NOT NULL AND longTextLazy IS NOT NULL",
            sort: "+id"
        );
        $this->assertIsInt($storable->id);
        $this->assertIsString($storable->name);
        $this->assertIsFloat($storable->floatNumber);
        $this->assertIsInt($storable->intNumber);
        $this->assertIsBool($storable->boolFlag);
        $this->assertInstanceOf(TestStorable2::class, $storable->selfReferenceOptional);
        $this->assertNull($storable->createUser);
        $this->assertInstanceOf(DateTime::class, $storable->dateTime);
        // test lazy
        $this->assertNull($storable->getOriginalDbValueForProperty("longTextLazy"));
        $this->assertIsString($storable->longTextLazy);
        $this->assertIsString($storable->getOriginalDbValueForProperty("longTextLazy"));
    }

    #[Depends("testDatatypesSetter")]
    public function testDatatypesNoPrefetch(): void
    {
        $storables = TestStorablePrefetch::getByCondition();
        $this->startRecordExecutedQueries();
        foreach ($storables as $storable) {
            $obj = $storable->otherReferenceNoPrefetch;
            // running twice to double-check if cache works fine
            $obj2 = $storable->otherReferenceNoPrefetch;
        }
        // when prefetch is disabled, every getter to a referenced storable requires a database getById
        $this->assertExecutedQueries(count($storables));
    }

    #[Depends("testDatatypesNoPrefetch")]
    public function testDatatypesPrefetch(): void
    {
        $storables = TestStorablePrefetch::getByCondition();
        $prefetchLimit = Storable::getStorableSchemaProperty(
            TestStorablePrefetch::class,
            "otherReferenceReducedPrefetch"
        )->prefetchLimit;
        // we have a reduced prefetch limit
        // a single getter will all prefetch max given number of storables
        // as we iterate over all storables, the prefetcher need a few step to prefetch all storables
        $requiredPrefetchQueries = ceil(count($storables) / $prefetchLimit);
        $this->startRecordExecutedQueries();
        foreach ($storables as $storable) {
            $obj = $storable->otherReferenceReducedPrefetch;
        }
        $this->assertExecutedQueries($requiredPrefetchQueries);
    }

    #[Depends("testDatatypesPrefetch")]
    public function testDeleteAll(): void
    {
        // delete with null does nothing
        Storable::deleteMultiple(null);
        $this->assertGreaterThan(0, TestStorable1::getByCondition());
        Storable::deleteMultiple(TestStorable1::getByCondition());
        $this->assertCount(0, TestStorable1::getByCondition());
    }

    #[Depends("testDeleteAll")]
    public function testMiscExceptions(): void
    {
        $this->assertExceptionOnCall(function () {
            $storable = new TestStorable2();
            $storable = clone $storable;
        });

        $this->assertExceptionOnCall(function () {
            $storable = new TestStorable2();
            $storable->notExist = 1;
        });

        $this->assertExceptionOnCall(function () {
            $storable = new TestStorable2();
            $foo = $storable->notExist;
        });

        $this->assertExceptionOnCall(function () {
            $storable = new TestStorable2();
            $storable->store();
        });

        $this->assertExceptionOnCall(function () {
            $storable = new TestStorable2();
            $storable->delete();
        });

        $storable = new TestStorableDeeper();
        $storable->store();
        $this->assertExceptionOnCall(function () use ($storable) {
            $storable->delete();
        });
        $storable->delete(true);

        $this->setSimulatedGetData(['redirect' => Url::create()]);
        $this->assertExceptionOnCall(function () {
            $jsCall = new JsCall("deleteStorable", null);
            Storable::onJsCall($jsCall);
        }, [], Redirect::class);
    }

    #[Depends("testMiscExceptions")]
    public function testDefaultStorables(): void
    {
        $storableFiles = FileUtils::getFiles(
            FileUtils::getModuleRootPath("Framelix") . "/src/Storable",
            "~\.php$~",
            true
        );
        foreach ($storableFiles as $storableFile) {
            $storabeClass = ClassUtils::getClassNameForFile($storableFile);
            $storableSchema = Storable::getStorableSchema($storabeClass);
            if ($storableSchema->abstract) {
                continue;
            }
            $this->assertStorableDefaultGetters(new $storabeClass());
        }
    }

    /**
     * Test value setter with all possible values
     * @param Storable $storable
     * @param string $propertyName
     * @param array $allowedTypes
     * @return void
     */
    private function assertStorablePropertyValueSetter(
        Storable $storable,
        string $propertyName,
        array $allowedTypes,
    ): void {
        foreach ($allowedTypes as $allowedType) {
            $storable->{$propertyName} = $this->dummyValues[$allowedType];
        }
        foreach ($this->dummyValues as $type => $value) {
            if (in_array($type, $allowedTypes)) {
                continue;
            }
            // testing normal values
            try {
                $storable->{$propertyName} = $value;
                $valid = false;
            } catch (Exception $e) {
                $valid = true;
            }
            $this->assertTrue($valid, "Property: $propertyName, Type: $type, Got: " . var_export($value, true));
        }
    }

    /**
     * Start recording executed queries
     * @return void
     */
    private function startRecordExecutedQueries(): void
    {
        Sql::$logExecutedQueries = true;
        $db = Sql::get('test');
        $db->executedQueries = [];
        $this->executedQueries = $db->executedQueriesCount;
    }

    /**
     * Stop recording and assert number of queries
     * @param int $queries
     * @return void
     */
    private function assertExecutedQueries(int $queries): void
    {
        $db = Sql::get('test');
        $now = $db->executedQueriesCount - $this->executedQueries;
        if ($queries !== $now) {
            echo implode("\n", $db->executedQueries);
        }
        $db->executedQueries = [];
        Sql::$logExecutedQueries = false;
        $this->assertSame(
            $queries,
            $now
        );
    }
}