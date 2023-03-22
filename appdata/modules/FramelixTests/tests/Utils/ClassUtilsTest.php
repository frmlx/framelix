<?php

namespace Utils;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\Storable\TestStorable1;
use Framelix\FramelixTests\TestCase;

use function realpath;

use const FRAMELIX_APP_ROOT;

final class ClassUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame('ClassUtilsTest', ClassUtils::getClassBaseName(__CLASS__));
        $this->assertSame('utils-classutilstest', ClassUtils::getHtmlClass(__CLASS__));
        $this->assertSame('framelixtests-storable-teststorable1', ClassUtils::getHtmlClass(TestStorable1::class));
        $this->assertSame('__framelixtests_storable_teststorable1__', ClassUtils::getLangKey(TestStorable1::class));
        $this->assertSame('__utils_classutilstest__', ClassUtils::getLangKey(__CLASS__));
        $this->assertSame('__utils_classutilstest_foo__', ClassUtils::getLangKey(__CLASS__, "foo"));
        ClassUtils::validateClassName(__CLASS__);
        $this->assertSame(
            ClassUtils::getClassNameForFile(__DIR__ . "/../../src/Storable/TestStorable1.php"),
            TestStorable1::class
        );
        $this->assertSame(ClassUtils::getClassNameForFile(__FILE__ . "NotExist"), null);
        $this->assertNull(
            ClassUtils::getFilePathForClassName("foo")
        );
        $this->assertNull(
            ClassUtils::getFilePathForClassName(TestStorable1::class . "NotExist")
        );
        $this->assertSame(
            ClassUtils::getFilePathForClassName(TestStorable1::class),
            FileUtils::normalizePath(realpath(__DIR__ . "/../../src/Storable/TestStorable1.php"))
        );
        $this->assertSame(
            ClassUtils::getFilePathForClassName(User::class),
            FRAMELIX_APP_ROOT . "/modules/Framelix/src/Storable/User.php"
        );
        $this->assertSame(ClassUtils::getModuleForClass(TestStorable1::class), "FramelixTests");
        $this->assertSame(ClassUtils::getModuleForClass(new TestStorable1()), "FramelixTests");
    }

    public function testExceptionInvalidClassName(): void
    {
        $this->assertExceptionOnCall(function () {
            ClassUtils::validateClassName("&hacked");
        });

        $this->assertExceptionOnCall(function () {
            ClassUtils::validateClassName("");
        });
    }
}
