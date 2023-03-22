<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\View;


class Index extends View\Backend\View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
    }
}