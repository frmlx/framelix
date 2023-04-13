<?php

namespace Framelix\FramelixStarter;

class Config
{
    public static function onRegister(): void
    {
        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixStarter", "scss", "test");
        $bundle->addFolder("vendor-frontend/scss", true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixStarter", "js", "test");
        $bundle->addFolder("vendor-frontend/js", true);
    }
}