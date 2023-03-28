<?php

namespace Framelix\FramelixDocs\Backend;


use Framelix\FramelixDocs\View\Index;
use Framelix\FramelixDocs\View\Setup;
use Framelix\FramelixDocs\View\SetupCoreDev;

class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    public function showContent(): void
    {
        $this->startGroup('Get started');
        $this->addLink(Index::class, "Welcome");
        $this->addLink(Setup::class);
        $this->addLink(SetupCoreDev::class);
        $this->showHtmlForLinkData();
    }
}