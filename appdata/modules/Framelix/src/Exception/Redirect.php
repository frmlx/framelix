<?php

namespace Framelix\Framelix\Exception;

use Exception;
use Framelix\Framelix\Url;
use Throwable;

/**
 * Redirect exception
 * Add a redirect header to the current response
 */
class Redirect extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 302,
        ?Throwable $previous = null,
        public ?Url $url = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}