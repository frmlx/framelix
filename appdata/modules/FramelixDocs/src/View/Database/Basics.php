<?php

namespace Framelix\FramelixDocs\View\Database;

use Framelix\Framelix\Db\Sql;
use Framelix\FramelixDocs\View\View;

class Basics extends View
{
    protected string $pageTitle = 'Database Basics';

    public function showContent(): void
    {
        ?>
        <p>
            Framelix is built for SQL databases. All ORM features are only available for SQL.
            However, Framelix fully supports MySQL/MariaDB and SQLite.
            For quick and tiny application, SQLite is the easiest way, as it requires no additional services.
            For productions and larger scale projects, MySQL is recommended.
            Our configuration builder will guide you through some variants.
            In any way, integration is seamless and you never have to worry about database much, after first setup.
            Framelix does most things for you.
        </p>
        <?= $this->getAnchoredTitle('storables', 'Storables - The way you store data in Framelix') ?>
        <p>
            With Framelix, working with the database directly is almost never required. Basically you just need to know
            how you write WHERE conditions.
            Everything else is managed by Framelix. You never need to: Update db scheme, writing inserts, deletes or
            updates, not even think about a db scheme in the first place.
        </p>
        <p>
            Framelix have its own ORM (Object Related Mapping) layer, which is simply called a <code>Storable</code>.
            A <code>Storable</code> is a PHP Object which have every functionality to get/set data in the database.
        </p>
        <p>
            Storables have extremely powerful auto-completion features and without <em>any</em> modification to your
            IDE, your editor should always know what you are currently using. This is one of the huge benefits of
            Framelix. It helps the developer to develop code fast. Auto-completion is a big part of that goal.
        </p>
        <?php
        echo $this->getLinkToInternalPage(Storables::class, 'Check out the detailed article about storables');
        ?>

        <?= $this->getAnchoredTitle('queries', 'Executing raw queries'); ?>
        <p>
            If you ever need to execute raw queries, you can do that.
        </p>
        <?php
        $this->startCodeRecording(function () {
            // using default db
            $db = Sql::get();

            // raw
            $db->query("INSERT ...");
            // just a nicer way to insert something
            $db->insert("table", ['name' => 'foo']);
            // simple update
            $db->update("table", ['name' => 'foo'], 'id = {0}', [3018]);
            // simple delete
            $db->delete("table", 'id = {0}', [3018]);

            // using anotherdb db
            $db = Sql::get('anotherdb');
            $db->query("INSERT ...");
        });
        $this->showRecordedCode('php');
        $this->getAnchoredTitle('queries', 'Executing raw queries');
        ?>
        <?= $this->getAnchoredTitle('fetchData', 'Fetch data'); ?>
        <p>
            If you ever need to fetch raw data, you can do that to.
        </p>
        <?php
        $this->startCodeRecording(function () {
            // using default db
            $db = Sql::get();
            // returns an array-array with column names as indexes
            $db->fetchAssoc("SELECT ...");
            // same as above but with parameters automatically escaped properly
            $db->fetchAssoc("SELECT ... FROM xxx WHERE name = {0}", ['brainfoolong']);
            // same as fetchAssoc, but only returns the first entry of it
            $db->fetchAssocOne("SELECT ...");
            // returns an array-array without column names
            $db->fetchArray("SELECT ...");
            // returns an array with the first column as value
            $db->fetchColumn("SELECT ...");
            // returns the first column of the first row
            $db->fetchOne("SELECT ...");
        });
        $this->showRecordedCode('php');
    }
}