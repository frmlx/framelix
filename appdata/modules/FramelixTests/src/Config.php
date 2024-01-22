<?php

namespace Framelix\FramelixTests;

use const FRAMELIX_USERDATA_FOLDER;

class Config
{
    /**
     * Called when the module is registered the first time
     * This is used for module defaults
     * @return void
     */
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::addSqliteConnection(
            FRAMELIX_MODULE,
            FRAMELIX_USERDATA_FOLDER . "/sqlite_" . FRAMELIX_MODULE . ".db"
        );

        \Framelix\Framelix\Config::$devMode = true;

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-folder");
        $bundle->addFolder('js', true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-path");
        $bundle->addFile('js/framelix-unit-test-jstest.js');

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-nocompile");
        $bundle->compile = false;
        $bundle->addFile('js/framelix-unit-test-jstest.js');

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle(
            "FramelixTests",
            "js",
            "test-nocompile-ignorefile"
        );
        $bundle->compile = false;
        $bundle->addFolder('js', true, ["js/framelix-unit-test-jstest2.js"]);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "scss", "test-folder");
        $bundle->addFolder('scss', true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "scss", "test-path");
        $bundle->addFile('scss/framelix-unit-test-scsstest.scss');

        if (defined('PHPUNIT_TESTS')) {
            // configured so that salt and test db connection is hardcoded for unit tests
            \Framelix\Framelix\Config::addSalt('jdTbhul2sd3yyaLQPfTFNToE42PcXOCC991SzzKlUrQhS1hhkdTIHufuJ8Sj6XPgd');
        }
    }
}