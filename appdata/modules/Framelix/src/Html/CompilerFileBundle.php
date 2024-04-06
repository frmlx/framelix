<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function array_values;
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
    ) {}

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
     * @param \Framelix\Framelix\Html\CompilerFileBundle[]|\Framelix\Framelix\Html\CompilerFileBundle|null $ignoreFilesFromBundles Ignores
     *     all files from given bundles
     * @return void
     */
    public function addFolder(
        string $relativeFolderPath,
        bool $recursive,
        array|CompilerFileBundle|null $ignoreFilesFromBundles = null
    ): void {
        $ignoredFiles = [];
        if ($ignoreFilesFromBundles) {
            /** @var \Framelix\Framelix\Html\CompilerFileBundle[] $arr */
            $arr = !is_array($ignoreFilesFromBundles) ? [$ignoreFilesFromBundles] : $ignoreFilesFromBundles;
            foreach ($arr as $ignoredBundle) {
                if ($ignoredBundle !== $this && $ignoredBundle instanceof CompilerFileBundle) {
                    $ignoredFiles = array_merge($ignoredFiles, $ignoredBundle->getFiles());
                }
            }
        }
        $this->entries[] = [
            "type" => "folder",
            "path" => $relativeFolderPath,
            "recursive" => $recursive,
            "ignoredFiles" => $ignoredFiles,
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
            "path" => $relativeFilePath,
        ];
    }

    /**
     * Get array of all real files that needed to compile into the bundle
     * @return array
     */
    public function getFiles(): array
    {
        $files = [];
        $ignoredFiles = [];
        foreach ($this->entries as $row) {
            if ($this->type === 'scss') {
                $bootstrapFile = FileUtils::getModuleRootPath($this->module) . "/scss/_compiler-bootstrap.scss";
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
                if ($row['ignoredFiles'] ?? null) {
                    $ignoredFiles = $row['ignoredFiles'];
                }
                $files = array_merge(
                    $files,
                    $folderFiles
                );
            }
        }
        // remove dupes and check for missing files
        $compileFiles = [];
        foreach ($files as $file) {
            $realFile = realpath($file);
            if (in_array($realFile, $ignoredFiles)) {
                continue;
            }
            if (!$realFile) {
                throw new FatalError('Cannot find file "' . $file . '" for compiler');
            }
            if (!isset($compileFiles[$realFile])) {
                $compileFiles[$realFile] = $realFile;
            }
        }
        return array_values($compileFiles);
    }

}