<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use JetBrains\PhpStorm\ExpectedValues;

use function file_exists;

class CompilerFileBundle
{

    /**
     * Automatically include into views
     * If false you need to add it by hand with $view->includeCompiledFile()
     * @var bool
     */
    public bool $pageAutoInclude = true;

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
        if (!file_exists($file)) {
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
}