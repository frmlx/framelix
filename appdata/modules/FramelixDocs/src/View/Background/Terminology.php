<?php

namespace Framelix\FramelixDocs\View\Background;

use Framelix\FramelixDocs\View\View;

class Terminology extends View
{
    protected string $pageTitle = 'Terminology / How things are named in Framelix';

    public function showContent(): void
    {
        ?>
        <p>
            You may come across some words and terms that you may not know, so let us explain the most used terms in
            Framelix and what they mean.
        </p>
        <?= $this->getAnchoredTitle('view', 'View') ?>
        <p>
            A View is basically an entry point for your application, the point where the user can visit your
            application, or in short: A url that can be loaded by your browser.

            Other frameworks often call the process Route/Routing. In Framelix Routing and actual output happens in the
            same place, in the View itself. No need of separate keep tracking of entry point routes. the place where you
            actually output is also the place how it can be accessed.
        </p>

        <?= $this->getAnchoredTitle('storable', 'Storable') ?>
        <p>
            A Storable as an object, that can be stored in the database. It contains all properties that can be saved in
            the database. Each property is a column in the database, simple as that. There is no need to create meta
            files or anything like that. One file, one purpose.
        </p>

        <?= $this->getAnchoredTitle('module', 'Module') ?>
        <p>
            A module in Framelix as basically everything in the folder <code>appdata/modules/{module}</code>. It
            contains the source for a complete application, or just a part of it. Framelix itself is just a module that
            loads itself.
        </p>
        <p>
            A module is most likely to be a complete application. But you can develop a shared module, which itself only contains code the is reused from other modules.
        </p>
        <?php
    }
}