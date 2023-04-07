<?php

namespace Framelix\FramelixDocs;

use Framelix\Framelix\Utils\FileUtils;

use const FRAMELIX_MODULE;

class Config
{
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FileUtils::getUserdataFilepath("database.db", false)
        );
        \Framelix\Framelix\Config::$languagesAvailable = ['en'];
        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "docs");
        $bundle->addFile("scss/docs.scss");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "vendor");
        $bundle->compile = false;
        $bundle->addFile("vendor-frontend/highlightjs/github-dark.css");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "docs");
        $bundle->addFile("js/docs.js");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "vendor");
        $bundle->compile = false;
        $bundle->addFile("vendor-frontend/highlightjs/highlight.min.js");
        $bundle->addFile("vendor-frontend/highlightjs/highlightjs-line-numbers.min.js");
    }
}