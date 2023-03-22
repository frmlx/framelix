<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\View\Backend\View;

/**
 * TestBackendView
 */
class TestBackendView extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
    }
}