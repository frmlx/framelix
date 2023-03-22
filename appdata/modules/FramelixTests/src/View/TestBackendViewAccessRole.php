<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\View\Backend\View;

/**
 * TestBackendViewAccessRole
 */
class TestBackendViewAccessRole extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "rolenotexist";

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