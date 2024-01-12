<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function array_values;
use function file_exists;
use function is_file;

class CompilerFileBundle
{

    /**
     * Automatically include into backend views that inherit from given view class
     * If null you need to add it by hand with HtmlUtils::getIncludeTagForBundle
     * @var string|null
     */
    public ?string $includeInBackendView = null;

    /**
     * Compile the bundle with babel/sass
     * If false, just copy original contents instead of compiled contents
     * @var bool
     */
    public bool $compile = true;

    /**
     * Put 'use strict'; at the top of the js bundle file
     * @var bool
     */
    public bool $jsStrict = true;

    /**
     * The added files/folders
     * @var array
     */
    public array $entries = [];

    public function __construct(
        public string $module,
        #[ExpectedValues(['js', 'scss'])] public string $type,
        public string $bundleId
    ) {
    }

    public function getGeneratedBundleFilePath(): string
    {
        $extension = $this->type === 'js' ? 'js' : 'css';
        return FileUtils::getModuleRootPath($this->module) . "/public/dist/$extension/$this->bundleId.min.$extension";
    }

    public function getGeneratedBundleUrl(): Url
    {
        $file = $this->getGeneratedBundleFilePath();
        if (!is_file($file)) {
            throw new FatalError("DistFile $file not exist");
        }
        return Url::getUrlToPublicFile($file);
    }

    /**
     * Add a folder with all files in it, relative from the bundles module folder
     * @param string $relativeFolderPath
     * @param bool $recursive
     * @param array|null $ignoreFilenames
     * @return void
     */
    public function addFolder(string $relativeFolderPath, bool $recursive, ?array $ignoreFilenames = null): void
    {
        $this->entries[] = [
            "type" => "folder",
            "path" => $relativeFolderPath,
            "recursive" => $recursive,
            "ignoreFilenames" => $ignoreFilenames
        ];
    }

    /**
     * Add a file, relative from the bundles module folder
     * @param string $relativeFilePath
     * @return void
     */
    public function addFile(string $relativeFilePath): void
    {
        $this->entries[] = [
            "type" => "file",
            "path" => $relativeFilePath
        ];
    }

    /**
     * Get array of all real files that needed to compile into the bundle
     * @return array
     */
    public function getFiles(): array
    {
        $files = [];
        foreach ($this->entries as $row) {
            if ($this->type === 'scss') {
                $bootstrapFile = FileUtils::getModuleRootPath($this->module) . "/vendor-frontend/scss/_compiler-bootstrap.scss";
                if (is_file($bootstrapFile)) {
                    $files[] = $bootstrapFile;
                }
            }
            if ($row['type'] === 'file') {
                $files[] = FileUtils::getModuleRootPath($this->module) . "/" . $row['path'];
            } elseif ($row['type'] === 'folder') {
                $path = FileUtils::getModuleRootPath($this->module) . "/" . $row['path'];
                $extensions = $this->type === 'js' ? 'js' : "(css|scss)";
                $folderFiles = FileUtils::getFiles(
                    $path,
                    "~\.$extensions$~",
                    $row['recursive'] ?? false
                );
                if (isset($row['ignoreFilenames'])) {
                    foreach ($folderFiles as $key => $file) {
                        if (in_array(basename($file), $row['ignoreFilenames'])) {
                            unset($folderFiles[$key]);
                        }
                    }
                }
                $files = array_merge(
                    $files,
                    $folderFiles
                );
            }
        }
        // remove dupes
        $compileFiles = [];
        foreach ($files as $file) {
            $file = realpath($file);
            if ($file && !isset($compileFiles[$file])) {
                $compileFiles[$file] = $file;
            }
        }
        return array_values($compileFiles);
    }
}