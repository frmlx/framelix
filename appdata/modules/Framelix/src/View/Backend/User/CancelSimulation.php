<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\View;

class CancelSimulation extends View
{
    protected string|bool $accessRole = true;

    public function onRequest(): void
    {
        $token = UserToken::getByCookie();
        $token->simulatedUser = null;
        $token->store();
        Toast::success('__framelix_simulateuser_canceled__');
        Url::create(Request::getGet('redirect') ?? Url::getApplicationUrl())->redirect();
    }
}