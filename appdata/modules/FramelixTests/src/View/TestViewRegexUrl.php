<?php

namespace Framelix\FramelixTests\View;

use Framelix\Framelix\View;

/**
 * TestViewRegexUrl
 */
class TestViewRegexUrl extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Custom url
     * @var string|null
     */
    protected ?string $customUrl = "~^/regex/(?<id>[0-9]+)~";

    /**
     * On request
     */
    public function onRequest(): void
    {
    }
}