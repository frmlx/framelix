<?php

namespace Utils;

use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use function basename;
use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function str_replace;

use const FRAMELIX_MODULE;
use const SCANDIR_SORT_DESCENDING;

final class FileUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame(
            "/framelix/userdata/FramelixTests/public/test.php",
            FileUtils::getUserdataFilepath("test.php", true)
        );
        $this->assertSame(str_replace("\\", "/", __DIR__), FileUtils::normalizePath(__DIR__));
        $this->assertSame(str_replace("\\", "/", __DIR__), FileUtils::normalizePath(__DIR__, true));
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 4)), FRAMELIX_APP_ROOT);
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 2)), FileUtils::getModuleRootPath(FRAMELIX_MODULE));
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 2)), FileUtils::getModuleRootPath(FRAMELIX_MODULE));
        $this->assertSame(
            "modules/FramelixTests/tests/Utils/" . basename(__FILE__),
            FileUtils::getRelativePathToBase(__FILE__)
        );
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test/.gitignore",
                "modules/FramelixTests/tmp/fileutils-test/test1",
                "modules/FramelixTests/tmp/fileutils-test/test1.txt"
            ],
            FileUtils::getFiles(__DIR__ . "/../../tmp/fileutils-test")
        );
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test/test1.txt",
                "modules/FramelixTests/tmp/fileutils-test/test1",
                "modules/FramelixTests/tmp/fileutils-test/.gitignore",
            ],
            FileUtils::getFiles(__DIR__ . "/../../tmp/fileutils-test", sortOrder: SCANDIR_SORT_DESCENDING)
        );
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test/test1.txt"
            ],
            FileUtils::getFiles(__DIR__ . "/../../tmp/fileutils-test", "~\.txt$~")
        );
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test/sub/test1.txt",
                "modules/FramelixTests/tmp/fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../tmp/fileutils-test", "~\.txt$~", true)
        );
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test/sub",
                "modules/FramelixTests/tmp/fileutils-test/sub/test1.txt",
                "modules/FramelixTests/tmp/fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../tmp/fileutils-test", "~\.txt$~", true, true)
        );
        $testFolder = __DIR__ . "/../../tmp/fileutils-test-tmp";
        FileUtils::deleteDirectory($testFolder);
        mkdir($testFolder);
        mkdir($testFolder . "/test");
        file_put_contents($testFolder . "/test.txt", "1");
        file_put_contents($testFolder . "/test/test.txt", "1");
        $this->assertFilelist(
            [
                "modules/FramelixTests/tmp/fileutils-test-tmp/test",
                "modules/FramelixTests/tmp/fileutils-test-tmp/test/test.txt",
                "modules/FramelixTests/tmp/fileutils-test-tmp/test.txt",
            ],
            FileUtils::getFiles($testFolder, null, true, true)
        );
        FileUtils::deleteDirectory($testFolder);
        $this->assertFilelist(
            [],
            FileUtils::getFiles($testFolder, null, true, true)
        );
        $this->assertTrue(!is_dir($testFolder));
    }

    /**
     * Assert a filelist to match exactly
     * @param string[] $expected
     * @param string[] $actual
     * @return void
     */
    private function assertFilelist(array $expected, array $actual): void
    {
        foreach ($actual as $key => $value) {
            $actual[$key] = FileUtils::getRelativePathToBase($value);
        }
        $this->assertSame($expected, $actual);
    }
}
