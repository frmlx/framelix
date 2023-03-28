<?php

namespace Framelix\FramelixDocs\View;

class Index extends View
{
    protected string $pageTitle = 'Framelix - A rich featured, Docker ready, Full-Stack PHP Framework';
    public function showContent(): void
    {
        echo $this->getAnchoredTitle('welcome', 'Welcome to Framelix');
        ?>
        <blockquote>
            Framelix is a rich featured, Docker ready, Full-Stack PHP framework with built-in backend and data
            management capabilities for internal/public data applications.
        </blockquote>
        <p>
            Follow the navigation to the left to check out all parts of Framelix. We have documentation and live
            examples for features in Framelix. The page you are currently viewing is also already live served by the
            Framelix backend.
        </p>
        <p>
            Everything you see right here is already powered by Framelix. This docs are built with Framelix and currently live serving as you see them.
            You see the <code>Default UI</code> of Framelix. With navigation to the left. Styles and features you will discover here, are the same that you can use directly in your own application.
        </p>
        <p>
            All code of Framelix is <?=$this->getLinkToExternalPage('https://github.com/NullixAT/framelix', 'Open-Source on Github')?>, including <?=$this->getLinkToExternalPage('https://github.com/NullixAT/framelix/tree/master/appdata/modules/FramelixTests', 'this docs itself')?>.
        </p>
        <?= $this->getAnchoredTitle('start', 'How to start development?'); ?>
        <p>
            To kickstart your development journey and to setup your environment, head to the <?=$this->getLinkToInternalPage(Setup::class)?>
        </p>
        <?php
    }
}