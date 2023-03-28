<?php

namespace Framelix\FramelixStarter\View;

class Index extends \Framelix\Framelix\View\Backend\View
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