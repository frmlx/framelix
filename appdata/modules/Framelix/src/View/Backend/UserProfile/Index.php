<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\View\Backend\View;

class Index extends View
{
    protected string|bool $accessRole = true;

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $tabs = new Tabs();
        $tabs->addTab('email', null, new Email());
        $tabs->addTab('password', null, new Password());
        $tabs->addTab('fido2', null, new Fido2());
        $tabs->addTab('twofactor', null, new TwoFactor());
        $tabs->show();
    }
}