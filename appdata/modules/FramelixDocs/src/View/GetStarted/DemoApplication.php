<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\FramelixDocs\View\View;

class DemoApplication extends View
{
    protected string $pageTitle = 'Demo Application';

    public function showContent(): void
    {
        ?>
        <p>
            We know, learning a new Framework is not that easy at first.
            To get the hang of it, we have created a complete Demo Application that you can inspect and try out to learn
            from it.
            It uses best-practice for Framelix features and try to teach you things as you go through the interface.
        </p>
        <?php
    }
}