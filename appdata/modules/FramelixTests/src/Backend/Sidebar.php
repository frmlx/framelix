<?php

namespace Framelix\FramelixTests\Backend;

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
        $this->showHtmlForLinkData();
    }
}