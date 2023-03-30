<?php

namespace Framelix\FramelixDocs\View\Database;

use Framelix\Framelix\Framelix;
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
            A storable is a PHP class with properties. Each property is becomes a column in the database.
            The database scheme is automatically managed by the framework, you never need to write queries to update your database structure.
            Don't worry, the system does nothing what can cause data-loss by default. You always have full control of when and what actually get updated in the database.
            More on that later.
            Let's see a very basic Storable here, our SimpleDemo storable.
        </p>
        <?php
        $this->showSourceFiles([__DIR__."/../../Storable/SimpleDemo.php"]);
        echo $this->getAnchoredTitle('breakdown', "Let's break it down");
    }
}