<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;

use function http_response_code;
use function implode;
use function is_array;

class JsCallView extends View
{
    protected string|bool $accessRole = "*";
    protected ?string $customUrl = "/jscv";

    public function onRequest(): void
    {
        $url = Url::create();
        if (!$url->verify()) {
            http_response_code(400);
            return;
        }
        $requestMethod = Request::getGet('method');
        if (is_array($requestMethod)) {
            $requestMethod = implode("::", $requestMethod);
        }
        $jsCall = new JsCall((string)Request::getGet('action'), Request::getBody());
        JsonUtils::output($jsCall->call((string)$requestMethod));
        throw new StopExecution();
    }
}