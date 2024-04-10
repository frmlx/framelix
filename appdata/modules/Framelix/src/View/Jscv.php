<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;

use function http_response_code;

/**
 * View that handles FramelixRequest.jsCall
 */
class Jscv extends View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        $url = Url::create();
        if (!$url->verify()) {
            http_response_code(400);
            JsonUtils::output(Lang::get('__framelix_url_expired__'));
            throw new StopExecution();
        }
        $requestMethod = Request::getGet('method');
        $jsCall = new JsCall((string)Request::getGet('action'), Request::getBody());
        JsonUtils::output($jsCall->call($requestMethod));
        throw new StopExecution();
    }
}