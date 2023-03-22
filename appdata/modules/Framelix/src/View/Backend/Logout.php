<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;

class Logout extends View
{
    protected string|bool $accessRole = true;

    public function onRequest(): void
    {
        UserToken::getByCookie()?->delete();
        UserToken::setCookieValue(null);
        Url::getApplicationUrl()->redirect();
    }

    public function showContent(): void
    {
    }
}