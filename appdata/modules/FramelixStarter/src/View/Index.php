<?php

namespace Framelix\FramelixStarter\View;

use Framelix\Framelix\View\Backend\View;

class Index extends View
{
    protected string $pageTitle = 'Framelix Starter Page';

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        echo 'Great. You have your app setup right.';
    }
}