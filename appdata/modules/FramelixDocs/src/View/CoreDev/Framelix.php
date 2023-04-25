<?php

namespace Framelix\FramelixDocs\View\CoreDev;

use Framelix\FramelixDocs\View\View;

class Framelix extends View
{
    protected string $pageTitle = 'Framelix development';

    public function showContent(): void
    {
        ?>
        <p>
            The development in the core consists of many parts that are working together. Here is a list of what
            sections Framelix is internally split into.
        </p>
        <?php
        echo $this->getAnchoredTitle('modules', 'Development modules');
        ?>
        <p>
            This repository have 5 modules, 4 of them have separate ports and entry points.
        </p>
        <ul>
            <li>Framelix - This is the core which is just the Framelix Framework itself. Everything you modify here, is
                used by every other module.
            </li>
            <li>FramelixDemo - A complete application (An accounting software demo). This show many use-cases of how you can use Framelix in action.
            </li>
            <li>FramelixDocs - This module contains the docs you are currently reading. So when you do changes in
                Framelix, you also should update the docs according to your changes.
            </li>
            <li>FramelixStarter - A small biolerplate module that a user of the Framelix framework just can unpack and
                use as a starting point for development.
            </li>
            <li>FramelixTests - This contains all tests. Unit tests and playwright end-to-end tests. Every change in the
                Framelix framework should have all new logic tested. 90%+ is the goal for code coverage.
            </li>
        </ul>
        <?php
    }
}