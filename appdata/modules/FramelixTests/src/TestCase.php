<?php

namespace Framelix\FramelixTests;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Db\Sqlite;
use Framelix\Framelix\Db\SqlStorableSchemeBuilder;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View;
use Framelix\FramelixTests\Storable\TestStorable1;
use ReflectionClass;
use ReflectionUnionType;
use Throwable;

use function call_user_func_array;
use function file_exists;
use function file_put_contents;
use function get_class;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ob_end_clean;
use function ob_start;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function unlink;
use function var_dump;

use const FRAMELIX_MODULE;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public ?int $setupTestDbType = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->setSimulatedUrl('http://localhost');
        switch ($this->setupTestDbType) {
            case Sql::TYPE_MYSQL:
                \Framelix\Framelix\Config::addMysqlConnection(
                    'test',
                    'unittests',
                    'localhost',
                    'root',
                    'app'
                );
                break;
            case Sql::TYPE_SQLITE:
                $file = FRAMELIX_USERDATA_FOLDER . "/test.db";
                \Framelix\Framelix\Config::addSqliteConnection(
                    'test',
                    $file
                );
                break;
        }
    }

    /**
     * Does call all static and public methods of an object
     * This is solely for php error checks, not for logic tests
     * This also not covers all methods and some will throw errors
     * It should be the base tests, later you add manual logic tests
     * @param object $object
     * @param array|null $ignoreMethods
     * @return void
     */
    public function callMethodsGeneric(object $object, ?array $ignoreMethods = null): void
    {
        $reflection = new ReflectionClass($object);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isAbstract() || $method->isPrivate() || $method->isProtected()) {
                continue;
            }
            $shortName = $method->getShortName();
            if (str_starts_with($shortName, "__")) {
                continue;
            }
            if ($ignoreMethods && in_array($shortName, $ignoreMethods)) {
                continue;
            }
            $args = [];
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isOptional()) {
                    break;
                }
                $value = null;
                $type = $parameter->getType();
                if ($type instanceof ReflectionUnionType) {
                    $type = $type->getTypes()[0];
                }
                $paramType = $type?->getName();
                switch ($paramType) {
                    case 'int':
                        $value = 1;
                        break;
                    case 'double':
                    case 'float':
                        $value = 1.22;
                        break;
                    case 'string':
                    case 'mixed':
                        $value = "test";
                        break;
                    case 'array':
                        $value = ["test"];
                        break;
                    case JsCall::class:
                        $value = new JsCall("testaction", null);
                        break;
                    case Date::class:
                        $value = Date::create("2000-01-01");
                        break;
                    case DateTime::class:
                        $value = DateTime::create("2000-01-01");
                        break;
                    case Storable::class:
                        $value = new TestStorable1();
                        break;
                    case HtmlAttributes::class:
                        $value = new HtmlAttributes();
                        break;
                    case Url::class:
                        $value = Url::create();
                        break;
                    case View::class:
                        $value = new View\Api();
                        break;
                    case Field::class:
                        $value = new Field\Text();
                        $value->name = "foobar";
                        break;
                }
                $args[] = $value;
            }
            Buffer::start();
            $method->invoke($object, ...$args);
            Buffer::get();
            $this->assertTrue(true);
        }
    }

    /**
     * Call all methods that each field must have
     * @param Field $field
     * @return void
     */
    public function callFormFieldDefaultMethods(Field $field): void
    {
        $testForm = new Form();
        $testForm->id = "test";

        $class = get_class($field);
        $clone = new $class();
        $clone->name = "foo";
        $testForm->addField($clone);

        // check required
        $clone->required = true;
        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $clone->validate());

        $clone->getVisibilityCondition()->equal('foo', 'test');
        $this->assertFalse($clone->isVisible());
        // just calling validate to pass the default validation tests
        // could be anything (string/bool)
        $clone->validate();

        $oldPost = $_POST;
        $this->setSimulatedPostData([$clone->name => 'test']);
        $this->assertTrue($clone->isVisible());
        $this->setSimulatedPostData($oldPost);

        $clone->defaultValue = "bla";

        $this->assertInstanceOf(PhpToJsData::class, $clone->jsonSerialize());

        ob_start();
        $clone->show();
        ob_end_clean();
    }

    /**
     * Call all storable interface methods on given class name
     * @param string $className
     * @return void
     */
    public function callStorableInterfaceMethods(string $className): void
    {
        $schema = new StorableSchema($className);
        $property = $schema->createProperty('test');
        $this->assertNull(call_user_func_array([$className, "setupSelfStorableSchemaProperty"], [$property]));
        call_user_func_array([$className, "createFromDbValue"], ["foo"]);
        call_user_func_array([$className, "createFromFormValue"], ["foo"]);
    }

    /**
     * Assert storables default getter
     * @param Storable $storable
     * @return void
     */
    public function assertStorableDefaultGetters(Storable $storable): void
    {
        $this->assertIsBool($storable->isReadable());
        $this->assertIsBool($storable->isEditable());
        $this->assertIsBool($storable->isDeletable());
        $this->assertTrue(is_int($storable->getDbValue()) || $storable->getDbValue() === null);
        $this->assertIsString($storable->getHtmlString());
        $this->assertIsString($storable->getRawTextString());
        $this->assertIsString($storable->getSortableValue());
        $this->assertTrue($storable->getDetailsUrl() instanceof Url || $storable->getDetailsUrl() === null);
    }

    /**
     * Setup database for tests
     * @param bool $simulateDefaultConnection If true, this will add a connection id "default" based on "test"
     * @return void
     */
    public function setupDatabase(bool $simulateDefaultConnection = false): void
    {
        // close opened connections to be clean when starting
        foreach (Sql::$instances as $instance) {
            $instance->disconnect();
        }
        Sql::$instances = [];
        if ($simulateDefaultConnection) {
            Config::$sqlConnections[FRAMELIX_MODULE] = Config::$sqlConnections['test'];
        }
        $this->cleanupDatabase();
        $db = Sql::get('test');
        $builder = new SqlStorableSchemeBuilder($db);
        $queries = $builder->getQueries();
        $builder->executeQueries($queries);
    }

    /**
     * Setup database after tests
     * Drop and recreate db
     * @return void
     */
    public function cleanupDatabase(): void
    {
        $db = Sql::get('test');
        if ($db instanceof Sqlite) {
            $db->disconnect();
            unlink($db->path);
            $db->connect();
        } else {
            $dbQuoted = $db->quoteIdentifier($db->database ?? '');
            $db->query("DROP DATABASE $dbQuoted");
            $db->query("CREATE DATABASE $dbQuoted");
            $db->query("USE $dbQuoted");
        }
    }

    /**
     * Add simulated file in $_FILES
     * @param string $fieldName
     * @param string $filedata
     * @param bool $isMultiple This will add the same file 2 times to simulate multiple upload
     * @param string|null $filename Override filename
     * @param int $simulateFileUploadErrorCode
     * @return void
     */
    public function addSimulatedFile(
        string $fieldName,
        string $filedata,
        bool $isMultiple,
        ?string $filename = null,
        int $simulateFileUploadErrorCode = 0
    ): void {
        $this->removeSimulatedFile($fieldName);
        if ($isMultiple) {
            $tmpName = __DIR__ . "/../tmp/" . $fieldName . "-0.txt";
            $_FILES[$fieldName]['name'][0] = $filename ?? $fieldName;
            $_FILES[$fieldName]['tmp_name'][0] = $tmpName;
            $_FILES[$fieldName]['size'][0] = (string)strlen($filedata);
            $_FILES[$fieldName]['type'][0] = '';
            $_FILES[$fieldName]['error'][0] = $simulateFileUploadErrorCode;
            if (!$simulateFileUploadErrorCode) {
                file_put_contents($tmpName, $filedata);
            }

            $tmpName = __DIR__ . "/../tmp/" . $fieldName . "-1.txt";
            $_FILES[$fieldName]['name'][1] = $filename ?? $fieldName;
            $_FILES[$fieldName]['tmp_name'][1] = $tmpName;
            $_FILES[$fieldName]['size'][1] = (string)strlen($filedata);
            $_FILES[$fieldName]['type'][1] = '';
            $_FILES[$fieldName]['error'][1] = $simulateFileUploadErrorCode;
            if (!$simulateFileUploadErrorCode) {
                file_put_contents($tmpName, $filedata);
            }
        } else {
            $tmpName = __DIR__ . "/../tmp/" . $fieldName . ".txt";
            $_FILES[$fieldName]['name'] = $filename ?? $fieldName;
            $_FILES[$fieldName]['tmp_name'] = $tmpName;
            $_FILES[$fieldName]['size'] = (string)strlen($filedata);
            $_FILES[$fieldName]['type'] = '';
            $_FILES[$fieldName]['error'] = $simulateFileUploadErrorCode;
            if (!$simulateFileUploadErrorCode) {
                file_put_contents($tmpName, $filedata);
            }
        }
    }

    /**
     * Remove simulated file in $_FILES
     * @param string $name
     * @return void
     */
    public function removeSimulatedFile(string $name): void
    {
        $tmpName = __DIR__ . "/../tmp/" . $name . ".txt";
        if (file_exists($tmpName)) {
            unlink($tmpName);
        }
        if (isset($_FILES[$name]['name'])) {
            if (is_array($_FILES[$name]['name'])) {
                foreach ($_FILES[$name]['name'] as $key => $filename) {
                    if (file_exists($_FILES[$name]['tmp_name'][$key])) {
                        unlink($_FILES[$name]['tmp_name'][$key]);
                    }
                }
                unset($_FILES[$name]);
            } else {
                if (file_exists($_FILES[$name]['tmp_name'])) {
                    unlink($_FILES[$name]['tmp_name']);
                }
                unset($_FILES[$name]);
            }
        }
    }

    /**
     * Set simulated header
     * @param string $name
     * @param string|null $value
     * @return void
     */
    public function setSimulatedHeader(string $name, ?string $value): void
    {
        $_SERVER[strtoupper($name)] = $value;
    }

    /**
     * Set simulated body data context
     * @param array $data
     * @return void
     */
    public function setSimulatedBodyData(array $data): void
    {
        Request::$requestBodyData['data'] = $data;
    }

    /**
     * Set simulated post data context
     * @param array $data
     * @return void
     */
    public function setSimulatedPostData(array $data): void
    {
        $_POST = $data;
    }

    /**
     * Set simulated get data context
     * @param array $data
     * @return void
     */
    public function setSimulatedGetData(array $data): void
    {
        $url = Url::create();
        $url->removeParameters();
        $url->addParameters($data);
        $_SERVER['REQUEST_URI'] = $url->getPathAndQueryString();
        if ($url->getHash()) {
            $_SERVER['REQUEST_URI'] .= "#" . $url->getHash();
        }
        $_GET = $data;
    }

    /**
     * Set server current url for unit test
     * @param string|Url $url
     * @return void
     */
    public function setSimulatedUrl(string|Url $url): void
    {
        if (is_string($url)) {
            $url = Url::create($url);
        } else {
            $url = clone $url;
        }
        $host = $url->urlData['host'] ?? 'localhost';
        if ($url->getUsername()) {
            $host = $url->getUsername() . ":" . $url->getPassword() . "@" . $host;
        }
        if ($url->getPort()) {
            $host .= ":" . $url->getPort();
        }
        $_SERVER['HTTPS'] = (($url->urlData['scheme'] ?? null) === "https") ? "on" : "off";
        $_SERVER['HTTP_HOST'] = $host;
        unset($url->urlData['scheme'], $url->urlData['host']);
        $_SERVER['REQUEST_URI'] = $url->getPath();
        if ($url->getHash()) {
            $_SERVER['REQUEST_URI'] .= "#" . $url->getHash();
        }
        $this->setSimulatedGetData($url->urlData['queryParameters'] ?? []);
    }

    /**
     * Set simulated user
     * @param mixed $roles
     *  null = No user
     *  true = A user only, without roles
     *  array = A user with given roles
     * @return void
     */
    public function setSimulatedUser(mixed $roles): void
    {
        if ($roles === null) {
            User::setCurrentUser(null);
            return;
        }
        $user = new User();
        $user->simulateRoles = [];
        if (is_array($roles)) {
            $user->simulateRoles = $roles;
        }
        User::setCurrentUser($user);
    }

    /**
     * Assert a success toast to be queued
     * Does reset the toast cache after calling this
     * @return void
     */
    public function assertToastSuccess(): void
    {
        $this->assertTrue(Toast::hasSuccess(), 'Success Toast required');
        Toast::getQueueMessages(true);
    }

    /**
     * Assert a warning toast to be queued
     * Does reset the toast cache after calling this
     * @return void
     */
    public function assertToastWarning(): void
    {
        $this->assertTrue(Toast::hasWarning(), 'Warning Toast required');
        Toast::getQueueMessages(true);
    }

    /**
     * Assert a error toast to be queued
     * Does reset the toast cache after calling this
     * @return void
     */
    public function assertToastError(): void
    {
        $this->assertTrue(Toast::hasError(), 'Error Toast required');
        Toast::getQueueMessages(true);
    }

    /**
     * Assert a info toast to be queued
     * Does reset the toast cache after calling this
     * @return void
     */
    public function assertToastInfo(): void
    {
        $this->assertTrue(Toast::hasInfo(), 'Info Toast required');
        Toast::getQueueMessages(true);
    }

    /**
     * Assert a specific exception on call a code
     * @param callable $call
     * @param array $callArgs
     * @param string $expectedType
     * @param string|null $expectedMessageRegex
     * @param bool $debugOutput Enable debug output to console to show insights
     * @return void
     */
    public function assertExceptionOnCall(
        callable $call,
        array $callArgs = [],
        string $expectedType = FatalError::class,
        ?string $expectedMessageRegex = null,
        bool $debugOutput = false
    ): void {
        try {
            call_user_func_array($call, $callArgs);
        } catch (Throwable $e) {
            if ($debugOutput) {
                var_dump("Exception DEBUG:\n\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            $this->assertSame($expectedType, get_class($e), $e->getMessage() . "\n" . $e->getTraceAsString());
            if ($expectedMessageRegex) {
                $this->assertMatchesRegularExpression("~$expectedMessageRegex~i", $e->getMessage());
            }
            return;
        }
        throw new Exception("Assert exception '" . $expectedType . "' to be thrown, but not exception was thrown");
    }
}