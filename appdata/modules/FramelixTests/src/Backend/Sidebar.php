<?php

namespace Framelix\FramelixTests\Backend;

use Framelix\FramelixTests\View\ModalWindow;

/**
 * Backend sidebar
 */
class Sidebar extends \Framelix\Framelix\Backend\Sidebar
{
    /**
     * Show the navigation content
     */
    public function showContent(): void
    {
        $this->startGroup('Layout');
        $this->addLink(ModalWindow::class, 'Modal Window');
        $this->showHtmlForLinkData();
    }
}