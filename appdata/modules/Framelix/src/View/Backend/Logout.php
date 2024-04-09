<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Storable\UserToken;

class Logout extends View
{

    protected string|bool $accessRole = true;

    public function onRequest(): void
    {
        UserToken::getByCookie()?->delete();
        UserToken::setCookieValue(null);
        Login::redirectToDefaultUrl();
    }

    public function showContent(): void {}

}