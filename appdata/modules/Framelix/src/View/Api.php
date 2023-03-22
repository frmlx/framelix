<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Exception\SoftError;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use ReflectionClass;

use function http_response_code;
use function strtolower;

class Api extends View
{
    protected string|bool $accessRole = "*";
    protected ?string $customUrl = "~^/api/(?<requestMethod>[A-Za-z0-9]+$)~";

    /**
     * Request type get|post|put|delete
     * @var string
     */
    protected string $requestType;

    public function onRequest(): void
    {
        $requestMethod = $this->customUrlParameters['requestMethod'];
        $reflection = new ReflectionClass($this);
        $method = $reflection->hasMethod($requestMethod) ? $reflection->getMethod($requestMethod) : null;
        if (!$method || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
            $this->error('Invalid api path/function');
        }
        $this->requestType = strtolower($_SERVER['REQUEST_METHOD'] ?? "get");
        $method->invoke($this);
    }

    public function downloadFile(): void
    {
        if (!Url::create()->verify(false)) {
            http_response_code(404);
            return;
        }
        $file = StorableFile::getById(
            Request::getGet('id'),
            Request::getGet('connectionId'),
            true
        );
        if (!$file) {
            http_response_code(404);
            return;
        }
        Response::download($file);
    }

    public function jscall(): void
    {
        $url = Url::create();
        if (!$url->verify(false)) {
            http_response_code(400);
            return;
        }
        $jsCall = new JsCall((string)Request::getGet('action'), Request::getBody());
        $this->success($jsCall->call((string)Request::getGet('phpMethod')));
    }

    /**
     * Require a specific role, return error if role does not match
     * @param mixed $role
     * @return void
     */
    public function requireRole(mixed $role): void
    {
        if (!User::hasRole($role)) {
            $this->error('User has not the required role');
        }
    }

    /**
     * Show success
     * @param mixed|null $result
     * @return never
     */
    public function success(mixed $result = null): never
    {
        JsonUtils::output($result);
        throw new SoftError();
    }

    /**
     * Show error response
     * @param string|null $result
     * @return never
     */
    public function error(?string $result = null): never
    {
        http_response_code(500);
        JsonUtils::output($result);
        throw new SoftError();
    }
}