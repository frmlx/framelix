<?php

namespace Utils;

use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use PHPUnit\Framework\Attributes\Depends;

use function basename;
use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function str_replace;

use const FRAMELIX_MODULE;
use const FRAMELIX_TMP_FOLDER;
use const SCANDIR_SORT_DESCENDING;

final class FileUtilsTest extends TestCase
{

    public function tests(): void
    {
        $this->assertSame(
            FRAMELIX_USERDATA_FOLDER . "/FramelixTests/public/test.php",
            FileUtils::getUserdataFilepath("test.php", true)
        );
        $this->assertSame(str_replace("\\", "/", __DIR__), FileUtils::normalizePath(__DIR__));
        $this->assertSame(str_replace("\\", "/", __DIR__), FileUtils::normalizePath(__DIR__, true));
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 4)), FRAMELIX_APPDATA_FOLDER);
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 2)), FileUtils::getModuleRootPath(FRAMELIX_MODULE));
        $this->assertSame(str_replace("\\", "/", dirname(__DIR__, 2)), FileUtils::getModuleRootPath(FRAMELIX_MODULE));
        $this->assertSame(
            "modules/FramelixTests/tests/Utils/" . basename(__FILE__),
            FileUtils::getRelativePathToBase(__FILE__)
        );
        $this->assertFilelist(
            [
                "fileutils-test/.gitignore",
                "fileutils-test/test1",
                "fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../test-files/fileutils-test")
        );
        $this->assertFilelist(
            [
                "fileutils-test/test1.txt",
                "fileutils-test/test1",
                "fileutils-test/.gitignore",
            ],
            FileUtils::getFiles(__DIR__ . "/../../test-files/fileutils-test", sortOrder: SCANDIR_SORT_DESCENDING)
        );
        $this->assertFilelist(
            [
                "fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../test-files/fileutils-test", "~\.txt$~")
        );
        $this->assertFilelist(
            [
                "fileutils-test/sub/test1.txt",
                "fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../test-files/fileutils-test", "~\.txt$~", true)
        );
        $this->assertFilelist(
            [
                "fileutils-test/sub",
                "fileutils-test/sub/test1.txt",
                "fileutils-test/test1.txt",
            ],
            FileUtils::getFiles(__DIR__ . "/../../test-files/fileutils-test", "~\.txt$~", true, true)
        );
        $testFolder = FRAMELIX_TMP_FOLDER . "/fileutils-test-tmp";
        FileUtils::deleteDirectory($testFolder);
        mkdir($testFolder);
        mkdir($testFolder . "/test");
        file_put_contents($testFolder . "/test.txt", "1");
        file_put_contents($testFolder . "/test/test.txt", "1");
        $files = FileUtils::getFiles($testFolder, null, true, true);
        $this->assertFilelist(
            [
                "fileutils-test-tmp/test",
                "fileutils-test-tmp/test/test.txt",
                "fileutils-test-tmp/test.txt",
            ],
            $files,
            FRAMELIX_TMP_FOLDER
        );
        FileUtils::deleteFiles($files, true);
        $this->assertFilelist(
            [],
            FileUtils::getFiles($testFolder, null, true, true)
        );
        FileUtils::deleteDirectory($testFolder);
        $this->assertTrue(!is_dir($testFolder));
    }

    public function testTmpFolder1(): void
    {
        FileUtils::deleteDirectory(FileUtils::getUserdataFilepath("tmp", false, autoCreateFolder: false));
        $folder = FileUtils::getTmpFolder(deleteOnScriptEnd: false);
        $file = fopen($folder."/test2.txt", "w");
        fwrite($file, "1");
        // write a normal file will close filehandlers automatically
        file_put_contents($folder . "/test1.tmp", '1');
        $this->assertCount(2, FileUtils::getFiles($folder));
    }

    #[Depends("testTmpFolder1")]
    public function testTmpFolder2(): void
    {
        $this->assertCount(
            0,
            FileUtils::getFiles(FileUtils::getUserdataFilepath("tmp", false, autoCreateFolder: false))
        );
    }

    /**
     * Assert a filelist to match exactly
     * @param string[] $expected
     * @param string[] $actual
     * @param string|null $baseFolder
     * @return void
     */
    private function assertFilelist(array $expected, array $actual, ?string $baseFolder = null): void
    {
        foreach ($actual as $key => $value) {
            $actual[$key] = FileUtils::getRelativePathToBase($value, $baseFolder ?? __DIR__ . "/../../test-files");
        }
        $this->assertSame($expected, $actual);
    }

}
