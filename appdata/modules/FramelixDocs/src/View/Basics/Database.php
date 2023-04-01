<?php

namespace Framelix\FramelixDocs\View\Basics;

use Framelix\Framelix\Db\Mysql;
use Framelix\FramelixDocs\View\Database\Storables;
use Framelix\FramelixDocs\View\View;

class Database extends View
{
    protected string $pageTitle = 'Database Basics';

    public function showContent(): void
    {
        ?>
        <p>
            Framelix runs with MariaDB (Mysql) by default. Our docker image comes with everything built-in, so you don't
            have to worry about setting up or managing your database.
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
            IDE, your editor should always know what you are currently using.
            There are <u>NO</u> (undocommented) magic getters, setters or methods. This is always a huge problem in
            other framworks, when they inject so many code in the background what the editor don't know about.
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
            $db = Mysql::get();

            // raw
            $db->query("INSERT ...");
            // just a nicer way to insert something
            $db->insert("table", ['name' => 'foo']);
            // simple update
            $db->update("table", ['name' => 'foo'], 'id = {0}', [3018]);
            // simple delete
            $db->delete("table", 'id = {0}', [3018]);

            // using anotherdb db
            $db = Mysql::get('anotherdb');
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
            $db = Mysql::get();
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