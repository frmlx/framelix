<?php

namespace Framelix\FramelixTests;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\User;

class Config
{
    /**
     * Called when the module is registered the first time
     * This is used for module defaults
     * @return void
     */
    public static function onRegister(): void
    {
        \Framelix\Framelix\Config::$devMode = true;

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-folder");
        $bundle->pageAutoInclude = false;
        $bundle->addFolder('js', true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-path");
        $bundle->pageAutoInclude = false;
        $bundle->addFile('js/framelix-unit-test-jstest.js');

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "js", "test-nocompile");
        $bundle->pageAutoInclude = false;
        $bundle->compile = false;
        $bundle->addFile('js/framelix-unit-test-jstest.js');

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle(
            "FramelixTests",
            "js",
            "test-nocompile-ignorefile"
        );
        $bundle->pageAutoInclude = false;
        $bundle->compile = false;
        $bundle->addFolder('js', true, ["framelix-unit-test-jstest2.js"]);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "scss", "test-folder");
        $bundle->pageAutoInclude = false;
        $bundle->addFolder('scss', true);

        $bundle = \Framelix\Framelix\Config::createCompilerFileBundle("FramelixTests", "scss", "test-path");
        $bundle->pageAutoInclude = false;
        $bundle->addFile('scss/framelix-unit-test-scsstest.scss');

        if (defined('PHPUNIT_TESTS')) {
            // configured so that no setup is needed and tests can assume the app is fully configured
            \Framelix\Framelix\Config::$appSetupDone = true;
            \Framelix\Framelix\Config::addSalt('jdTbhul2sd3yyaLQPfTFNToE42PcXOCC991SzzKlUrQhS1hhkdTIHufuJ8Sj6XPgd');
            \Framelix\Framelix\Config::addDbConnection('default', 'localhost', 3306, 'root', 'app', 'app');
            \Framelix\Framelix\Config::addDbConnection('test', 'localhost', 3306, 'root', 'app', 'unittests');

            // instantiate db if not yet done, to fix issue when a test is failed prev.
            $gen = new MysqlStorableSchemeBuilder(Mysql::get());
            if (!$gen->getExistingTables()) {
                $gen->executeQueries($gen->getSafeQueries());
            }

            // create default user if not yet exist
            if (!User::getByConditionOne()) {
                $user = new User();
                $user->email = "unit@tests.local";
                $user->setPassword('unit@tests.local');
                $user->addRole('admin');
                $user->addRole('dev');
                $user->flagLocked = false;
                $user->store();
            }
        }
    }
}