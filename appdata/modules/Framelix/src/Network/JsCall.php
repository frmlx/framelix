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
use function get_class;
use function is_array;
use function method_exists;
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
     * @param callable|array $callable A callable is only supported as array, not as a closure
     * @param string $action
     * @param array|null $additionalUrlParameters Additional GET url parameters to pass by, this are signed so they cannot be manipulated by the user
     * @param bool $signWithCurrentUserToken If true, then sign with current user token, so this url can only be verified by the same user
     * @param int $maxLifetime Max url lifetime in seconds, set to 0 if unlimited
     * @return Url
     */
    public static function getSignedUrl(
        callable|array $callable,
        string $action,
        ?array $additionalUrlParameters = null,
        bool $signWithCurrentUserToken = true,
        int $maxLifetime = 86400
    ): Url {
        if (!is_array($callable) || count($callable) !== 2) {
            throw new FatalError("\$callable must be an array of 2 values [className, methodName]");
        }
        return View::getUrl(View\Jscv::class)
            ->setParameter('method', $callable)
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
     * @param callable|array $callable A callable is only supported as array, not as a closure
     * @return mixed The result of the invoked call
     */
    public function call(callable|array $callable): mixed
    {
        if (!is_array($callable) || count($callable) !== 2) {
            throw new FatalError("\$callable must be an array of 2 values [className, methodName]");
        }
        $reflectionMethod = null;
        if ($callable[0] && class_exists($callable[0])) {
            if (method_exists($callable[0], $callable[1])) {
                $reflection = new ReflectionClass($callable[0]);
                $method = $reflection->getMethod($callable[1]);
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
        $returnValue = $reflectionMethod->invoke(null, $this);
        $output = Buffer::get();
        $hasBuffer = strlen(trim($output)) > 0;
        $hasReturn = $returnValue !== null;
        $hasResult = $this->result !== null;
        $sources = 0;
        if ($hasBuffer) {
            $sources++;
        }
        if ($hasReturn) {
            $sources++;
        }
        if ($hasResult) {
            $sources++;
        }
        if ($sources > 1) {
            throw new FatalError("Cannot mix multiple JsCall return values and outputs");
        }
        if ($hasBuffer) {
            return $output;
        }
        if ($hasReturn) {
            return $returnValue;
        }
        return $this->result;
    }

}