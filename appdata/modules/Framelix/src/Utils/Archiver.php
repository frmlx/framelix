<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Exception\FatalError;

use function array_unshift;
use function count;
use function explode;
use function is_dir;
use function is_file;
use function scandir;

/**
 * Archiver utility for tar, zip, etc...
 * We use 7zip internally
 */
class Archiver
{
    /**
     * List all files from an existing archive file
     * @param string $archivePath
     * @param array|null $commandParams Additional command params, see https://sevenzip.osdn.jp/chm/cmdline/commands/list.htm
     * @return array{"Path": string, "Folder": string, "Size": string, "Packed Size": string, "Modified": string, "Created": string, "Accessed": string, "Attributes": string, "Encrypted": string, "CRC": string, "Method": string, "Characteristics": string, "Host OS": string, "Version": string, "Volume Index": string, "Offset": string}
     */
    public static function listFiles(
        string $archivePath,
        ?array $commandParams = null
    ): array {
        if (!$commandParams) {
            $commandParams = [];
        }
        array_unshift($commandParams, $archivePath);
        $shell = Shell::prepare(
            '7z l -slt {*}',
            $commandParams
        );
        $shell->execute();
        $arr = [];
        $fileListStarted = false;
        $index = 0;
        foreach ($shell->output as $line) {
            $line = trim($line);
            if ($line === '----------') {
                $fileListStarted = true;
                continue;
            }

            if ($fileListStarted) {
                if ($line === '') {
                    $index++;
                    continue;
                }
                $exp = explode(" = ", $line, 2);
                if (count($exp) === 2) {
                    $arr[$index][$exp[0]] = $exp[1];
                }
            }
        }
        return $arr;
    }

    /**
     * Remove files from an existing archive file
     * @param string $archivePath
     * @param string $filePathInArchive
     * @param array|null $commandParams Additional command params, see https://sevenzip.osdn.jp/chm/cmdline/commands/delete.htm
     * @return Shell
     */
    public static function removeFile(
        string $archivePath,
        string $filePathInArchive,
        ?array $commandParams = null
    ): Shell {
        if (!$commandParams) {
            $commandParams = [];
        }
        array_unshift($commandParams, $filePathInArchive);
        array_unshift($commandParams, $archivePath);
        $shell = Shell::prepare(
            '7z d {*}',
            $commandParams
        );
        $shell->execute();
        return $shell;
    }

    /**
     * Add files to an file
     * Does create the zip file if not yet exist
     * Does update the file in zip if already exists
     * @param string $archivePath
     * @param string[] $files Key is path in archive, value is full filepath on disk
     * @param int|null $compressionLevel See https://sevenzip.osdn.jp/chm/cmdline/switches/method.htm#Zip
     */
    public static function addFiles(
        string $archivePath,
        array $files,
        ?int $compressionLevel = 5
    ): void {
        foreach ($files as $pathInArchive => $pathOnDisk) {
            $params = [];
            if ($compressionLevel !== null) {
                $params[] = "-mx" . $compressionLevel;
            }
            $params[] = $archivePath;
            $params[] = $pathOnDisk;
            $shell = Shell::prepare(
                '7z u -spf {*}',
                $params
            );
            $shell->execute();
            // @codeCoverageIgnoreStart
            if ($shell->status) {
                throw new FatalError(
                    "Error adding archive file: " . Shell::convertCliOutputToHtml(
                        $shell->output,
                        false
                    ) . " - " . $shell->cmd
                );
            }
            // remove file before renaming to avoid conflicts
            self::removeFile($archivePath, $pathInArchive);
            $shell = Shell::prepare(
                '7z rn {*}',
                [$archivePath, $pathOnDisk, $pathInArchive]
            );
            $shell->execute();
            if ($shell->status) {
                throw new FatalError(
                    "Error adding archive file: " . Shell::convertCliOutputToHtml(
                        $shell->output,
                        false
                    ) . " - " . $shell->cmd
                );
            }
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Extract an archive into given directory
     * @param string $archivePath
     * @param string $outputDirectory
     * @param bool $skipNotEmptyDirectory If true, it unpacks even if directory is not empty
     * @return void
     */
    public static function extractTo(
        string $archivePath,
        string $outputDirectory,
        bool $skipNotEmptyDirectory = false
    ): void {
        if (!is_file($archivePath)) {
            throw new FatalError("'$archivePath' is no file");
        }
        if (!is_dir($outputDirectory)) {
            throw new FatalError("'$outputDirectory' is no directory");
        }
        if (!$skipNotEmptyDirectory) {
            $files = scandir($outputDirectory);
            if (count($files) > 2) {
                throw new FatalError("'$outputDirectory' is not empty");
            }
        }
        $shell = Shell::prepare('7z x {*}', [$archivePath, "-o" . $outputDirectory, "-r", "-y"]);
        $shell->execute();
        if ($shell->status) {
            throw new FatalError("Error extracting archive: " . Shell::convertCliOutputToHtml($shell->output, false));
        }
    }
}