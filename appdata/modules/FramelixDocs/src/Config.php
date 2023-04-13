<?php

namespace Framelix\FramelixDocs;

use const FRAMELIX_MODULE;
use const FRAMELIX_USERDATA_FOLDER;

class Config
{
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FRAMELIX_USERDATA_FOLDER . "/sqlite_" . FRAMELIX_MODULE . ".db"
        );
        \Framelix\Framelix\Config::$languagesAvailable = ['en'];
        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "docs");
        $bundle->addFile("vendor-frontend/scss/docs.scss");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "vendor");
        $bundle->compile = false;
        $bundle->addFile("vendor-frontend/highlightjs/github-dark.css");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "docs");
        $bundle->addFile("vendor-frontend/js/docs.js");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "vendor");
        $bundle->compile = false;
        $bundle->addFile("vendor-frontend/highlightjs/highlight.min.js");
        $bundle->addFile("vendor-frontend/highlightjs/highlightjs-line-numbers.min.js");
    }
}