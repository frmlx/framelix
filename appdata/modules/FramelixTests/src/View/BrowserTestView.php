<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;

use function header;

class BrowserTestView extends View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        header('x-auth: ' . Request::getHeader('HTTP_AUTHORIZATION'));
        echo JsonUtils::encode(
            ['get' => $_GET, 'post' => $_POST, 'body' => Request::getBody(), 'method' => $_SERVER['REQUEST_METHOD']]
        );
    }
}