<?php

namespace Framelix\FramelixDocs\View\Database;

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
        $this->showSourceFiles([SimpleDemoEntry::class]);
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
        echo $this->getAnchoredTitle('override', "Override of functionality and defination with setupStorableSchema");
        ?>
        The line <code>protected static function setupStorableSchema(StorableSchema
        $selfStorableSchema)</code> in this example is optional, but you can use it to provide a lot more details to the behaviour in the database. As you can see here, we have used it to override a properties db column type and added an index to a column.
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
    }
}