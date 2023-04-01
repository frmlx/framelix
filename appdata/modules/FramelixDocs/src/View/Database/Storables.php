<?php

namespace Framelix\FramelixDocs\View\Database;

use Framelix\Framelix\Db\StorablePropertyInterface;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Storable\Storable;
use Framelix\FramelixDocs\Storable\SimpleDemoEntry;
use Framelix\FramelixDocs\View\View;

class Storables extends View
{
    protected string $pageTitle = 'Storables - The way you store data in Framelix';

    public function showContent(): void
    {
        ?>
        <p>
            <code>Storables</code> are a very important core feature of Framelix.
            With <code>Storables</code> you basically store, modify, update, delete everything in your database.
            A storable is a PHP class with properties. Each property become a column in the database.
            The database scheme is automatically managed by the framework, you never need to write queries to update
            your database structure.
            Don't worry, the system does nothing what can cause data-loss by default. You always have full control of
            when and what actually get updated in the database.
            More on that later.
            Let's see a very basic Storable here, our SimpleDemo storable.
        </p>
        <?php
        $this->addSourceFile(SimpleDemoEntry::class);
        $this->showSourceFiles();
        echo $this->getAnchoredTitle('breakdown', "Let's break it down - The class doc comments");
        ?>
        <p>
            The <code>@property</code> doc comments define which properties a storable have. This is the <em>only</em>
            place where you need to define the properties to a storable. The framework does extract all @property
            annotations and parses the type and name of a property.
            Each default PHP type like <code>string, int, bool, float</code> will be the same column type in the
            database. A type that can be optional is marked with <code>|null</code>. If it is not optional, you cannot
            store it with a value of NULL.
        </p>
        <p>
            As you see in this example, we have some special properties. <code>mixed|null $anyJsonData</code> simple can
            hold any data and will be <code>longtext</code> in the database. The data is automatically converted to a
            JSON string in the database, so you can run JSON database functions on that one.
        </p>
        <p>
            <code>DateTime|null $lastLogin</code> hold a DATETIME in the database. You can make own individual classes
            to be valid as a property. More on the bellow.
        </p>
        <p>
            <code>SimpleDemoEntry|null $referenceEntry</code> hold a reference to another <code>Storable</code> in the
            database. In the database this will be a BIGINT, as it only holds the ID of the given storable. The
            framework resolves this to the required Storable when you need it to fetch.
        </p>
        <p>
            <code>SimpleDemoEntry|null $referenceEntry</code> hold a reference to another <code>Storable</code> in the
            database. In the database this will be a BIGINT, as it only holds the ID of the given storable. The
            framework resolves this to the required Storable when you need it to fetch.
        </p>
        <p>
            <code>SimpleDemoEntry[]|null $arrayOfReferences</code> hold an array references to other
            <code>Storable</code> in the database. In the database this will be a longtext, the same as JSON. It only
            holds the IDs of the given storables. The framework resolves this to the required array of Storables when
            you need it to fetch.
        </p>
        <?php

        echo $this->getAnchoredTitle('ids', "IDs");
        ?>
        <p>
            Each saved storable has a unique id across ALL storables in the database. This is done by only one table has
            an auto_increment id, which is on <code><?= StorableSchema::ID_TABLE ?></code>. At the time a storable is
            inserted the first time, an entry
            in <code><?= StorableSchema::ID_TABLE ?></code> is created which generates a new unique. This id is then
            used in the storables own table
            as primary key on column <code>id</code>.

            So the result is, if you only have an id and know nothing else, you can find out the exact entry from the
            database, even when you don't know the storable type, because this id is gueranteed to not be used for
            another
            storable.
        </p>
        <?php
        echo $this->getAnchoredTitle('inheritance', "Inheritance and make a class a storable");
        ?>
        <p>
            To make a storable, you have to extend your class at least from <code>Storable</code> or <code>StorableExtended</code>.
            <code>Storable</code> just provides all features for a storable, with one property, <code>id</code>. <code>StorableExtended</code>
            includes 4 more properties which automatically stores <code>createTime, updateTime, createUser,
                updateUser</code> on the corresponding store action. This is recommended for almost all Storables, when
            you ever want to know the create and update infos.
        </p>
        <?php
        echo $this->getAnchoredTitle('storableObjects', "Make any class a storable property");
        ?>
        <p>
            Framelix uses the interface <code>StorablePropertyInterface</code> to make PHP class an allowed storable
            property, like the built-in <code>DateTime, Date and Time</code>.
            This interface requires you to set proper information of how the PHP class get stored and fetched from the
            database. It's similar to the process of serialize and unserialize.
        </p>
        <?php
        $this->addSourceFile(StorablePropertyInterface::class);
        $this->showSourceFiles();
        echo $this->getAnchoredTitle('override', "Override of functionality and defination with setupStorableSchema");
        ?>
        The line <code>protected static function setupStorableSchema(StorableSchema
        $selfStorableSchema)</code> in the
        <code>SimpleDemoEntry</code> is optional, but you can use it to provide a lot more details to the behaviour in the database. As you can see here, we have used it to override a properties db column type and added an index to a column.
        To find out which options you have, just use the auto-completion feature of your IDE, it gives you all the information you need for coding.
        <?php
        echo $this->getAnchoredTitle('create', "Your first entry - Store, Update, Delete");
        ?>
        So we have talked a lot about how the structure is defined. Let's create your first entry.
        <?php
        $this->startCodeRecording(function () {
            $entry = new SimpleDemoEntry();
            $entry->clientId = $this->clientId; // clientId isset outside of this scope
            $entry->email = "test@test.local";
            $entry->flagActive = false;
            $entry->store();

            // ... later on, updating is the same
            $entry->email = "another@test.local";
            $entry->store();

            // ... and some other time, deleting is a one-liner
            $entry->delete();
        });
        $this->showRecordedCode('php');
        ?>
        <p>
            As you can see here, the code is straight forward. Every property and method is fully autocompleted
            by your IDE.
        </p>
        <?php
        echo $this->getAnchoredTitle('fetch', "Fetching storables");
        ?>
        <p>
            Once you have created storables in the database, you need some to fetch the things later on. For this there
            are a handful of usefull methods to use.
        </p>
        <?php
        echo $this->getAnchoredTitle(
            'getByCondition',
            "Fetch with MyStorable::getById[s], MyStorable::getByCondition[One]"
        );
        ?>
        <p>
            This are the functions you need and will use the most when fetching storables from the database. Let's see
            some code.
        </p>
        <?php
        $this->startCodeRecording(function () {
            $entry = SimpleDemoEntry::getById(23);
            $entries = SimpleDemoEntry::getByIds([23, 24]);
            $entry = SimpleDemoEntry::getByConditionOne('email = {0}', ['test@test.local'], "-id");
            $entries = SimpleDemoEntry::getByCondition('email = {0}', ['test@test.local'], "-id");
        });
        $this->showRecordedCode('php');
        ?>
        <p>
            It will be redundant to explain you the functions, because the code documentation on the class itself is
            very good to get you in-depth information.
            But let's explain some specials of <code>getByCondition</code>
        </p>
        <p>
            The first parameter is basically comparable to a default mysql <code>WHERE</code> condition.
            The placeholders in brackets <code>{xxx}</code> will be replaced with the corresponding keys in the second
            parameter <code>$parameters</code>.<br/>
            All placeholders are automatically proper sql escaped, so you can pass direct user input with need to worry
            about SQL injections.
        </p>
        <p>
            <code>Depth-Joins</code> is a special feature in the condition part of <code>getByCondition</code>. If you
            have referenced storables, as for example by default there is a <code>createUser</code> property, which
            itself is another Storable called <code>User</code>. This <code>User</code> have other properties, and so
            on.
            Now, with depth-joins, you can write a condition intuitively like a <code>path</code> to the property you
            want to compare.
            So lets see an example.
            This is all handled by the framework and generate required joins automatically for you in the background. No
            need to do any join by yourself.
        </p>

        <?php
        $this->startCodeRecording(function () {
            $entry = SimpleDemoEntry::getByConditionOne(
                'createUser.flagLocked = {0} && email = {1}',
                [true, 'test@test.local']
            );
        });
        $this->showRecordedCode('php');
        ?>

        <p>
            This example will fetch a <code>SimpleDemoEntry</code>, where it's referenced property
            <code>createUser</code> will have a <code>User</code> referenced, where it's property is <code>flagLocked =
                true</code>
        </p>
        <?php
        echo $this->getAnchoredTitle('unknown', "Fetching when you know only ID but not the type");
        ?>
        <p>
            There can be a point, where you only have an ID from a Storable, but you don't know the Storables class. No
            problem. You can can <code>getById</code> even from the top-most abstract <code>Storable</code> method.
        </p>
        <?php
        $this->startCodeRecording(function () {
            $entry = Storable::getById(23);
        });
        $this->showRecordedCode('php');


        echo $this->getAnchoredTitle('cache', "Storable Caching");
        ?>
        <p>
            Framelix make use of a clever caching mechanism, to try you to save a lot of queries to the database.
            Basically, whenever you use <code>getById</code> fetch, the framework will check if the Storable already
            have been fetched in the past and return the same instance that already exist.<br/>
            This procedure is internaly used as often as possible. For example when fetching property references.<br/>
            Let's say you have 100 Storables, each have the same <code>createUser</code> attached. You can call <code>->createUser</code>
            on each of this 100 storable individually, but there is only one query for the database. All other 99 calls
            make use of the internal framework Storable cache. Pretty cool, right?
        </p>
        <?php
    }
}