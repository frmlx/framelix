<?php

namespace Framelix\FramelixDocs;

class Config
{
    public static function onRegister(): void
    {
        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "scss", "docs");
        $bundle->addFile("scss/docs.scss");

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixDocs", "js", "docs");
        $bundle->addFile("js/docs.js");
    }
}