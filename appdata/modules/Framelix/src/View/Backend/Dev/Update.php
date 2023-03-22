<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\View\Backend\View;

class Update extends View
{
    protected string|bool $accessRole = "dev";
    protected bool $devModeOnly = true;

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $tabs = new Tabs();
        $tabs->addTab('update-database', null, new UpdateDatabase());
        $tabs->show();
    }
}