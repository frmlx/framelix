<?php

namespace Utils;

use Framelix\Framelix\Utils\Archiver;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use function json_encode;

use const FRAMELIX_TMP_FOLDER;

final class ArchiverTest extends TestCase
{

    public function testExtract(): void
    {
        $tarFile = __DIR__ . "/../../test-files/test.zip";
        $tmpFolder = FRAMELIX_TMP_FOLDER . "/tartest";
        FileUtils::deleteDirectory($tmpFolder);
        mkdir($tmpFolder);

        $this->assertExceptionOnCall(function () use ($tarFile, $tmpFolder) {
            Archiver::extractTo($tarFile . "NotExist", $tmpFolder);
        });

        $this->assertExceptionOnCall(function () use ($tarFile, $tmpFolder) {
            Archiver::extractTo($tarFile, $tmpFolder . "NotExist");
        });

        $this->assertExceptionOnCall(function () use ($tarFile, $tmpFolder) {
            Archiver::extractTo(__FILE__, $tmpFolder);
        });

        Archiver::extractTo($tarFile, $tmpFolder, true);
        $this->assertFilelist([
            'tartest/fileutils-test/sub/test1',
            'tartest/fileutils-test/sub/test1.txt',
            'tartest/fileutils-test/test1',
            'tartest/fileutils-test/test1.txt'
        ], $tmpFolder);
        $list = Archiver::listFiles($tarFile);
        $this->assertSame(
            '["fileutils-test","fileutils-test\/sub","fileutils-test\/sub\/test1","fileutils-test\/sub\/test1.txt","fileutils-test\/test1","fileutils-test\/test1.txt"]',
            json_encode(ArrayUtils::map($list, 'Path'))
        );

        $this->assertExceptionOnCall(function () use ($tarFile, $tmpFolder) {
            Archiver::extractTo($tarFile, $tmpFolder);
        });

        FileUtils::deleteDirectory($tmpFolder);
    }

    public function testCreate(): void
    {
        $tarCreateFile = FRAMELIX_TMP_FOLDER . "/tartest/test.tar";
        $tmpFolder = FRAMELIX_TMP_FOLDER . "/tartest";
        $packFolder = __DIR__ . "/../../test-files/fileutils-test";

        FileUtils::deleteDirectory($tmpFolder);
        mkdir($tmpFolder);

        $packFiles = [];
        $files = FileUtils::getFiles($packFolder, null, true, true);
        foreach ($files as $file) {
            $packFiles[FileUtils::getRelativePathToBase($file, $packFolder)] = $file;
        }
        Archiver::addFiles($tarCreateFile, $packFiles);

        Archiver::extractTo($tarCreateFile, $tmpFolder, true);
        $this->assertFilelist([
            'tartest/.gitignore',
            'tartest/sub/test1',
            'tartest/sub/test1.txt',
            'tartest/test.tar',
            'tartest/test1',
            'tartest/test1.txt'
        ], $tmpFolder);
        FileUtils::deleteDirectory($tmpFolder);
    }

    /**
     * Assert a filelist to match exactly in given folder
     * @param string[] $expected
     * @param string $folder
     * @return void
     */
    private function assertFilelist(array $expected, string $folder): void
    {
        $actual = FileUtils::getFiles($folder, null, true);
        foreach ($actual as $key => $value) {
            $actual[$key] = FileUtils::getRelativePathToBase($value, FRAMELIX_TMP_FOLDER);
        }
        $this->assertSame($expected, $actual);
    }
}
