<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View;
use ReflectionClass;
use ReflectionUnionType;

use function class_exists;
use function count;
use function explode;
use function get_class;
use function method_exists;
use function preg_replace;
use function str_contains;
use function strlen;
use function trim;

/**
 * Js Call with data passed to javascript FramelixRequest.jsCall
 * Example PHP Method:
 * public static function onJsCall(JsCall $jsCall): void {}
 */
class JsCall
{
    /**
     * The result to return
     * @var mixed
     */
    public mixed $result = null;

    /**
     * Get signed url to point to the js call view
     * @param string|callable|array $phpMethod A callable is only supported as array, not as a closure
     * @param string $action
     * @param array|null $additionalUrlParameters Additional array parameters to pass by
     * @param bool $signWithCurrentUserToken If true, then sign with current user token, so this url can only be verified by the same user
     * @param int $maxLifetime Max url lifetime in seconds, set to 0 if unlimited
     * @return Url
     */
    public static function getUrl(
        string|callable|array $phpMethod,
        string $action,
        ?array $additionalUrlParameters = null,
        bool $signWithCurrentUserToken = true,
        int $maxLifetime = 86400
    ): Url {
        return View::getUrl(View\JsCallView::class)
            ->setParameter('method', $phpMethod)
            ->setParameter('action', $action)
            ->addParameters($additionalUrlParameters)
            ->sign($signWithCurrentUserToken, $maxLifetime);
    }

    /**
     * Constructor
     * @param string $action The action passed to FramelixRequest.jsCall
     * @param mixed $parameters The parameters passed to FramelixRequest.jsCall
     */
    public function __construct(
        public string $action,
        public mixed $parameters
    ) {
    }

    /**
     * Call given callable method and passing this instance as parameter
     * Does verify the target function if it accepts a valid JsCall parameter
     * @param string $callableMethod
     * @return mixed The result of the invoked call
     */
    public function call(string $callableMethod): mixed
    {
        // validate if the requested php method exist and accept valid parameters
        $phpMethod = preg_replace("~[^a-z0-9_\\\\:]~i", "", $callableMethod);
        if (!str_contains($phpMethod, "::")) {
            $phpMethod .= "::onJsCall";
        }
        $reflectionMethod = null;
        $split = explode("::", $phpMethod);
        if ($split[0] && class_exists($split[0])) {
            if (method_exists($split[0], $split[1])) {
                $reflection = new ReflectionClass($split[0]);
                $method = $reflection->getMethod($split[1]);
                if ($method->isStatic()) {
                    $parameters = $method->getParameters();
                    if (count($parameters) === 1) {
                        $parameter = $parameters[0];
                        $type = $parameter->getType();
                        $types = [];
                        if ($type instanceof ReflectionUnionType) {
                            $types = $type->getTypes();
                        } else {
                            $types[] = $type;
                        }
                        foreach ($types as $type) {
                            if ($type->getName() === get_class($this)) {
                                $reflectionMethod = $method;
                                break;
                            }
                        }
                    }
                }
            }
        }
        if (!$reflectionMethod) {
            throw new FatalError(
                'Invalid php method - Expect a static method with first parameter must be of ' . get_class($this)
            );
        }
        Buffer::start();
        $reflectionMethod->invoke(null, $this);
        $output = Buffer::get();
        if (strlen(trim($output)) > 0) {
            if (isset($this->result)) {
                throw new FatalError("Cannot mix buffer output and \$jsCall->result");
            }
            return $output;
        }
        return $this->result;
    }
}