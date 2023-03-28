<?php

namespace Framelix\FramelixStarter\Backend;


use Framelix\FramelixStarter\View\Index;

class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    public function showContent(): void
    {
        $this->startGroup('Get started');
        $this->addLink(Index::class, "Welcome");
        $this->showHtmlForLinkData();
    }
}