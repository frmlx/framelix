<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\FramelixDocs\Config;
use Framelix\FramelixDocs\View\View;

class DemoApplication extends View
{
    protected string $pageTitle = 'Demo Application';

    public function showContent(): void
    {
        ?>
        <p>
            We know, learning a new Framework is not that easy at first.
            To get the hang of it, we have created a complete <code>Demo Application</code> that you can inspect and try
            out to learn
            from it.
            It uses best-practice for Framelix features and try to teach you things as you go through the interface.
        </p>
        <framelix-button block href="<?= Config::$demoAppUrl ?>" target="_blank" icon="70c" theme="primary">Let's
            check out the demo application here
        </framelix-button>
        <p>
            The demo application will automatically reset everything each hour.
            All data that you modify or enter there will be lost every hour.
        </p>
        <?php
    }
}