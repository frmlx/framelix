<?php

namespace Utils;

use Framelix\Framelix\Utils\Archiver;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

use function json_encode;

final class ArchiverTest extends TestCase
{

    public function testExtract(): void
    {
        $tarFile = __DIR__ . "/../../tmp/test.tar";
        $tmpFolder = __DIR__ . "/../../tmp/tartest";
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
            'modules/FramelixTests/tmp/tartest/fileutils-test/.gitignore',
            'modules/FramelixTests/tmp/tartest/fileutils-test/sub/test1',
            'modules/FramelixTests/tmp/tartest/fileutils-test/sub/test1.txt',
            'modules/FramelixTests/tmp/tartest/fileutils-test/test1',
            'modules/FramelixTests/tmp/tartest/fileutils-test/test1.txt'
        ], $tmpFolder);
        $list = Archiver::listFiles($tarFile);
        $this->assertSame(
            '["fileutils-test","fileutils-test\/.gitignore","fileutils-test\/sub","fileutils-test\/sub\/test1","fileutils-test\/sub\/test1.txt","fileutils-test\/test1","fileutils-test\/test1.txt"]',
            json_encode(ArrayUtils::map($list, 'Path'))
        );

        $this->assertExceptionOnCall(function () use ($tarFile, $tmpFolder) {
            Archiver::extractTo($tarFile, $tmpFolder);
        });

        FileUtils::deleteDirectory($tmpFolder);
    }

    public function testCreate(): void
    {
        $tarCreateFile = __DIR__ . "/../../tmp/tartest/test.tar";
        $tmpFolder = __DIR__ . "/../../tmp/tartest";
        $packFolder = __DIR__ . "/../../tmp/fileutils-test";

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
            'modules/FramelixTests/tmp/tartest/.gitignore',
            'modules/FramelixTests/tmp/tartest/sub/test1',
            'modules/FramelixTests/tmp/tartest/sub/test1.txt',
            'modules/FramelixTests/tmp/tartest/test.tar',
            'modules/FramelixTests/tmp/tartest/test1',
            'modules/FramelixTests/tmp/tartest/test1.txt'
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
            $actual[$key] = FileUtils::getRelativePathToBase($value);
        }
        $this->assertSame($expected, $actual);
    }
}
