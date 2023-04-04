<?php

namespace Framelix\FramelixDocs\View\Database;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\FramelixDocs\View\View;

class SchemeGenerator extends View
{
    protected string $pageTitle = 'DB Scheme';

    public function showContent(): void
    {
        ?>
        <p>
            As explained already in other parts of the docs, you don't need to do any sql scheme yourself. All is
            generated automatically and out of the properties you have defined in your storables.
        </p>
        <p>
            In the backend, when you are a user with the role <code>dev</code> and when you are locally in development
            mode , you have the option to update your scheme after a Storable property change in the sidebar.
        </p>
        <p>
            If you deploy your applications to production and have any scheme update in it, Framelix will automatically
            update the
            scheme when you restart your Docker container. No worry, any change that will modify any existing column
            will not be executed automatically.
            For that you need to write your own <code>appWarmup</code> code in the <code>Console</code> class of your
            module.
        </p>
        <p>
            There is a simple workflow to update your scheme by hand, if required.
        </p>
        <?php
        $this->startCodeRecording(function () {
            $db = Mysql::get();
            $builder = new MysqlStorableSchemeBuilder($db);
            $safeQueries = $builder->getSafeQueries(); // only queries that do not need to modify existing columns/data
            $unsafeQueries = $builder->getUnsafeQueries(); // queries that will need to modify existing columns/data
            $builder->executeQueries($safeQueries);
            $builder->executeQueries($unsafeQueries);
        });
        $this->showRecordedCode('php');
        ?>
        <p>
            Also there are console commands in the docker container to update from the command line.
        </p>
        <?php
        $this->showCodeBlock("framelix_console '*' updateDatabaseSafe\nframelix_console '*' updateDatabaseUnsafe", codeLanguage: 'bash');
    }
}